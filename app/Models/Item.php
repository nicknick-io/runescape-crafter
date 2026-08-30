<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['members' => 'boolean', 'tradeable' => 'boolean', 'ge_updated_at' => 'datetime']; }
    public function recipe(): HasOne { return $this->hasOne(Recipe::class, 'output_item_id'); }
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        return "https://secure.runescape.com/m=itemdb_rs/obj_big.gif?id={$this->rs_id}";
    }
}
