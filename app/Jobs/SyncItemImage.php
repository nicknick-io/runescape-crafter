<?php

namespace App\Jobs;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncItemImage implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $itemId) {}

    public function handle(): void
    {
        $item = Item::find($this->itemId);
        if (! $item) return;
        $response = Http::retry(3, 500)->timeout(20)->get('https://secure.runescape.com/m=itemdb_rs/obj_big.gif', ['id' => $item->rs_id]);
        if (! $response->successful() || $response->body() === '' || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) return;

        $path = "items/{$item->rs_id}.gif";
        Storage::disk('public')->put($path, $response->body());
        Item::whereKey($this->itemId)->update(['image_path' => $path]);
    }
}
