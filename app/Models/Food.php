<?php

namespace App\Models;

use App\Enums\FoodStatus;
use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'partner_id',
    'category_id',
    'name',
    'slug',
    'description',
    'image',
    'original_price',
    'rescue_price',
    'stock',
    'pickup_start',
    'pickup_end',
    'status',
])]
class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'foods';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FoodStatus::class,
            'original_price' => 'integer',
            'rescue_price' => 'integer',
            'stock' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getDiscountPercentAttribute(): int
    {
        if ($this->original_price === 0) {
            return 0;
        }

        return (int) round(
            (($this->original_price - $this->rescue_price) / $this->original_price) * 100
        );
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
