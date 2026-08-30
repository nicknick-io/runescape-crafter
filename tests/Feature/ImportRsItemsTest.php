<?php

use App\Models\Item;
use App\Jobs\UpdateItemPrice;
use App\Jobs\SyncItemImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('imports item metadata while preserving local GE prices', function () {
    Item::create(['rs_id' => 4151, 'name' => 'Old name', 'tradeable' => true, 'value' => 1, 'ge_price' => 123]);
    Http::fake(['https://chisel.weirdgloop.org/*' => Http::response(['version' => 1.0, '4151' => ['id' => 4151, 'name' => 'Abyssal whip', 'members' => true, 'limit' => 70, 'value' => 120001, 'icon' => 'Abyssal_whip.png']])]);

    $this->artisan('rs:items:import')->assertSuccessful();

    expect(Item::where('rs_id', 4151)->firstOrFail())->name->toBe('Abyssal whip')->ge_price->toBe(123)->members->toBeTrue();
});

it('updates a local item price from the GE endpoint', function () {
    $item = Item::create(['rs_id' => 4151, 'name' => 'Abyssal whip', 'tradeable' => true, 'value' => 120001]);
    Http::fake(['https://api.weirdgloop.org/*' => Http::response(['4151' => ['price' => 1820345, 'timestamp' => 1756200000]])]);

    (new UpdateItemPrice($item->id))->handle();

    expect($item->fresh())->ge_price->toBe(1820345)->ge_updated_at->not->toBeNull();
});

it('stores a downloaded item image locally', function () {
    Storage::fake('public');
    $item = Item::create(['rs_id' => 4151, 'name' => 'Abyssal whip', 'tradeable' => true, 'value' => 120001]);
    Http::fake(['https://secure.runescape.com/*' => Http::response('gif-content', 200, ['Content-Type' => 'image/gif'])]);

    (new SyncItemImage($item->id))->handle();

    Storage::disk('public')->assertExists('items/4151.gif');
    expect($item->fresh())->image_path->toBe('items/4151.gif');
});
