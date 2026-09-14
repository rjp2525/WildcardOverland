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
         *
         * Guarded, like everything else here, because this migration failed
         * partway through once and has to be safe to run again.
         */
        if (Schema::hasColumn('recipe_sections', 'intro')) {
            Schema::table('recipe_sections', function (Blueprint $table) {
                $table->dropColumn('intro');
            });
        }

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
     *
     * The data is converted first and the type changed afterwards, and the
     * order is the whole point. MySQL validates every existing row while it
     * runs the ALTER, so a column still holding "<p>hello</p>" cannot become
     * json: the statement fails and takes the deploy with it. SQLite does
     * not check, which is exactly why the wrong order looked fine in tests.
     */
    protected function convert(string $table, string $column): void
    {
        // An empty string is not valid JSON either, and means nothing here.
        DB::table($table)->where($column, '')->update([$column => null]);

        DB::table($table)
            ->whereNotNull($column)
            ->orderBy('id')
            ->each(function (object $row) use ($table, $column): void {
                $value = (string) $row->{$column};

                // Safe to run twice: a row already converted is left alone
                // rather than being re-encoded as a paragraph of JSON.
                if (static::isDocument($value)) {
                    return;
                }

                DB::table($table)->where('id', $row->id)->update([
                    $column => json_encode(HtmlToTipTap::convert($value)),
                ]);
            });

        // Every row is now valid JSON or null, so the type change is safe.
        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->json($column)->nullable()->change();
        });
    }

    protected static function isDocument(string $value): bool
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) && ($decoded['type'] ?? null) === 'doc';
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
