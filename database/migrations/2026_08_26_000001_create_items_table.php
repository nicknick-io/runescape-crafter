<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rs_id')->unique();
            $table->string('name')->index();
            $table->boolean('members')->default(false)->index();
            $table->boolean('tradeable')->default(false)->index();
            $table->unsignedInteger('value')->default(0);
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('ge_price')->nullable();
            $table->timestamp('ge_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('items'); }
};
