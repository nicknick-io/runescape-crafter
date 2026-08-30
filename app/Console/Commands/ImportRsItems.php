<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportRsItems extends Command
{
    protected $signature = 'rs:items:import';
    protected $description = 'Import RuneScape item metadata into the local database';

    public function handle(): int
    {
        $payload = Http::retry(3, 500)->timeout(120)->get('https://chisel.weirdgloop.org/gazproj/gazbot/rs_dump.json')->throw()->json();
        $items = collect(data_get($payload, 'items', $payload))
            ->filter(fn (mixed $item): bool => is_array($item) && isset($item['id'], $item['name']))
            ->all();

        foreach (array_chunk($items, 500, true) as $chunk) {
            $rows = collect($chunk)->map(function (array $item, int|string $key): array {
                return ['rs_id' => (int) ($item['id'] ?? $key), 'name' => $item['name'], 'members' => (bool) ($item['members'] ?? false), 'tradeable' => (bool) ($item['tradeable'] ?? isset($item['limit'])), 'value' => (int) ($item['value'] ?? 0), 'icon' => $item['icon'] ?? $item['icon_filename'] ?? null, 'created_at' => now(), 'updated_at' => now()];
            })->values()->all();
            Item::upsert($rows, ['rs_id'], ['name', 'members', 'tradeable', 'value', 'icon', 'updated_at']);
        }
        $this->info('Item metadata imported. Existing GE prices were preserved.');
        return self::SUCCESS;
    }
}
