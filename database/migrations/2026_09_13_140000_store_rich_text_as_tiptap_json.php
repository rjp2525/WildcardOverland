<?php

use App\Support\RichText\HtmlToTipTap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rich text becomes the editor's own JSON rather than HTML.
 *
 * HTML in a column is a string someone downstream has to trust, and the only
 * way to render it was to hand it to the browser as-is. JSON is a tree, and
 * one renderer walks it and writes the markup itself, so nothing reaches a
 * page that the application did not put there.
 *
 * Several fields that were plain text become rich at the same time, because
 * a step that says "leave it alone for 60 to 90 seconds" wants the seconds
 * in bold and could not say so.
 */
return new class extends Migration
{
    /** Table => columns that hold prose. */
    protected array $columns = [
        'trips' => ['content'],
        'recipes' => ['notes', 'method_intro'],
        'recipe_sections' => ['body'],
        'recipe_steps' => ['body'],
        'recipe_step_tips' => ['body'],
        'recipe_ingredient_groups' => ['note'],
    ];

    public function up(): void
    {
        /*
         * The section lead sentence was a second, plainer body. One rich
         * field says the same thing and is one less decision to make.
         */
        Schema::table('recipe_sections', function (Blueprint $table) {
            $table->dropColumn('intro');
        });

        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                $this->convert($table, $column);
            }
        }

        /*
         * A trip summary is a teaser and a meta description. It was being
         * edited as rich text and then printed as plain, so its tags were
         * showing up in search results. It goes back to being plain text.
         */
        DB::table('trips')->whereNotNull('summary')->orderBy('id')->each(function (object $row): void {
            $plain = trim(html_entity_decode(strip_tags((string) $row->summary), ENT_QUOTES, 'UTF-8'));

            DB::table('trips')->where('id', $row->id)->update(['summary' => $plain ?: null]);
        });
    }

    /**
     * Rewrites one column in place, converting whatever is already in it.
     */
    protected function convert(string $table, string $column): void
    {
        $existing = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->pluck($column, 'id');

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->json($column)->nullable()->change();
        });

        foreach ($existing as $id => $value) {
            DB::table($table)->where('id', $id)->update([
                $column => json_encode(HtmlToTipTap::convert((string) $value)),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->longText($column)->nullable()->change();
                });
            }
        }

        Schema::table('recipe_sections', function (Blueprint $table) {
            $table->text('intro')->nullable()->after('title');
        });
    }
};
