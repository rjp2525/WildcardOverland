<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the homepage gallery be curated rather than showing whatever was
 * uploaded most recently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->boolean('featured')
                ->default(false)
                ->index()
                ->after('private');
            $table->unsignedInteger('sort_order')
                ->default(0)
                ->after('featured');
            $table->string('caption')
                ->nullable()
                ->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['featured', 'sort_order', 'caption']);
        });
    }
};
