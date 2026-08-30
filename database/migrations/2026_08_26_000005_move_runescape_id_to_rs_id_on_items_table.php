<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        // Fresh installations already receive rs_id from the original migration.
        if (Schema::hasColumn('items', 'rs_id')) return;

        if (DB::getDriverName() !== 'sqlite') {
            throw new RuntimeException('Existing item IDs require the SQLite migration path. Back up production data before converting a non-SQLite database.');
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        Schema::create('items_rebuilt', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rs_id')->unique();
            $table->string('name')->index();
            $table->boolean('members')->default(false)->index();
            $table->boolean('tradeable')->default(false)->index();
            $table->unsignedInteger('value')->default(0);
            $table->string('icon')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('ge_price')->nullable();
            $table->timestamp('ge_updated_at')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO items_rebuilt (rs_id, name, members, tradeable, value, icon, image_path, ge_price, ge_updated_at, created_at, updated_at) SELECT id, name, members, tradeable, value, icon, image_path, ge_price, ge_updated_at, created_at, updated_at FROM items');
        DB::statement('UPDATE recipes SET output_item_id = (SELECT id FROM items_rebuilt WHERE rs_id = recipes.output_item_id)');
        DB::statement('UPDATE recipe_ingredients SET item_id = (SELECT id FROM items_rebuilt WHERE rs_id = recipe_ingredients.item_id)');
        Schema::drop('items');
        Schema::rename('items_rebuilt', 'items');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // A reverse conversion would destroy locally assigned IDs, so it is intentionally not automatic.
    }
};
