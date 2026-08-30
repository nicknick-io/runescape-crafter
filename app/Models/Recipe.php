<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['xp' => 'decimal:2', 'needs_review' => 'boolean']; }
    public function outputItem(): BelongsTo { return $this->belongsTo(Item::class, 'output_item_id'); }
    public function tool(): BelongsTo { return $this->belongsTo(Item::class, 'tool_id'); }
    public function ingredients(): HasMany { return $this->hasMany(RecipeIngredient::class); }
}
