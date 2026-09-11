<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')
                ->primary();
            $table->string('name')
                ->nullable()
                ->index();
            $table->string('filename');
            $table->string('extension');
            $table->integer('size');
            $table->string('stored_path');
            $table->string('stored_name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
