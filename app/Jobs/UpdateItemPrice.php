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

class UpdateItemPrice implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $itemId) {}

    public function handle(): void
    {
        $item = Item::find($this->itemId);
        if (! $item) return;
        $payload = Http::retry(3, 500)->timeout(15)->get('https://api.weirdgloop.org/exchange/history/rs/latest', ['id' => $item->rs_id])->throw()->json();
        $price = data_get($payload, "{$item->rs_id}.price");
        if ($price === null) return;
        Item::whereKey($this->itemId)->update(['ge_price' => (int) $price, 'ge_updated_at' => now()]);
    }
}
