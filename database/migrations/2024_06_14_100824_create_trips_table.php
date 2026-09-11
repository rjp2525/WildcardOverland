<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('slug')
                ->unique()
                ->index();
            $table->string('name');
            $table->string('headline')
                ->nullable();
            $table->text('summary')
                ->nullable();
            $table->longText('content')
                ->nullable();
            $table->date('start_date')
                ->nullable();
            $table->date('end_date')
                ->nullable();
            $table->integer('calculated_nights')
                ->nullable();
            $table->boolean('is_draft')
                ->default(false);
            $table->dateTime('published_at')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
