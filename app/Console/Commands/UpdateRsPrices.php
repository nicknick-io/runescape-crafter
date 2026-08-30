<?php

namespace App\Console\Commands;

use App\Jobs\UpdateItemPrice;
use App\Models\Item;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class UpdateRsPrices extends Command
{
    protected $signature = 'rs:prices:update {--all : Update all tradeable items, including fresh prices}';
    protected $description = 'Queue updates for stale local Grand Exchange prices';

    public function handle(): int
    {
        $items = Item::query()
            ->when(! $this->option('all'), fn ($query) => $query->where(fn ($query) => $query->whereNull('ge_updated_at')->orWhere('ge_updated_at', '<', now()->subMinutes(15))))
            ->pluck('id');
        if ($items->isEmpty()) { $this->info('No stale prices found.'); return self::SUCCESS; }
        foreach ($items->chunk(100) as $index => $chunk) {
            Bus::batch($chunk->map(fn (int $id) => new UpdateItemPrice($id))->all())
                ->name('RS3 GE price refresh '.($index + 1))
                ->allowFailures()
                ->dispatch();
        }
        $this->info("Queued {$items->count()} price updates in {$items->chunk(100)->count()} batches.");
        return self::SUCCESS;
    }
}
