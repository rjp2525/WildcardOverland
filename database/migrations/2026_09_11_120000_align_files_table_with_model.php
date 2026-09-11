<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The files table was created with a different column set than the File model
 * declares as fillable. The model is the more complete design - the Glide
 * asset pipeline needs the mime type and the originating disk - so the table
 * is brought in line with it here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->renameColumn('filename', 'original_filename');
            $table->renameColumn('extension', 'original_extension');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('stored_name');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->string('mime')
                ->after('original_extension');
            $table->string('hash', 64)
                ->nullable()
                ->index()
                ->after('mime');
            $table->string('type')
                ->default('content')
                ->index()
                ->after('hash');
            $table->string('disk')
                ->default('s3')
                ->after('stored_path');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['mime', 'hash', 'type', 'disk']);
            $table->string('stored_name');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->renameColumn('original_filename', 'filename');
            $table->renameColumn('original_extension', 'extension');
        });
    }
};
