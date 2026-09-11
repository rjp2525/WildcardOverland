<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_modifications', function (Blueprint $table) {
            $table->id();
            $table->string('vendor')
                ->nullable();
            $table->string('purchased_from')
                ->nullable();
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->date('purchase_date')
                ->nullable();
            $table->date('install_date')
                ->index()
                ->nullable();
            $table->integer('cost')
                ->nullable();
            $table->string('url')
                ->nullable();
            $table->boolean('shown_on_timeline')
                ->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_modifications');
    }
};
