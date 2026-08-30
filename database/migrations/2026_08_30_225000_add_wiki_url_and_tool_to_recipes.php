<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('wiki_url')->nullable()->comment('RuneScape wiki URL for this recipe');
            $table->foreignId('tool_id')->nullable()->constrained('items')->onDelete('set null')->comment('Tool required for this recipe (e.g., chisel, hammer)');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropColumn(['wiki_url', 'tool_id']);
        });
    }
};
