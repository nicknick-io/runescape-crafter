<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['quantity' => 'decimal:3']; }
    public function recipe(): BelongsTo { return $this->belongsTo(Recipe::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
