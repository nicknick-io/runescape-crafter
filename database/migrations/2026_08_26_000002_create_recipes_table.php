<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('output_item_id')->unique()->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('output_quantity')->default(1);
            $table->string('skill')->index();
            $table->unsignedSmallInteger('level_required')->default(1);
            $table->decimal('xp', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('recipes'); }
};
