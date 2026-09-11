<?php

use App\Models\NavigationLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('navigation_links', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(NavigationLink::class, 'parent_id')
                ->nullable()
                ->constrained('navigation_links');
            $table->integer('order')
                ->nullable()
                ->index();
            $table->string('name');
            $table->string('aria_label')
                ->nullable();
            $table->string('route_name')
                ->nullable();
            $table->boolean('enabled')
                ->default(true)
                ->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_links');
    }
};
