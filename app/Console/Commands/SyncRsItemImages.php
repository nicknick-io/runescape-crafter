<?php

namespace App\Console\Commands;

use App\Jobs\SyncItemImage;
use App\Models\Item;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncRsItemImages extends Command
{
    protected $signature = 'rs:items:images:sync {--all : Re-download existing local images}';
    protected $description = 'Queue local downloads of RuneScape item images';

    public function handle(): int
    {
        $ids = Item::query()->when(! $this->option('all'), fn ($query) => $query->whereNull('image_path'))->pluck('id');
        if ($ids->isEmpty()) { $this->info('No item images need syncing.'); return self::SUCCESS; }

        foreach ($ids->chunk(100) as $index => $chunk) {
            Bus::batch($chunk->map(fn (int $id) => new SyncItemImage($id))->all())
                ->name('RS3 item image sync '.($index + 1))
                ->allowFailures()
                ->dispatch();
        }
        $this->info("Queued {$ids->count()} item image downloads in {$ids->chunk(100)->count()} batches.");

        return self::SUCCESS;
    }
}
