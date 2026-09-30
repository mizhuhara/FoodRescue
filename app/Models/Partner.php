<?php

namespace App\Models;

use App\Enums\PartnerStatus;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'business_name',
    'description',
    'phone',
    'address',
    'latitude',
    'longitude',
    'logo',
    'status',
    'verified_at',
])]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Food, $this>
     */
    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<Partner>  $query
     * @return Builder<Partner>
     */
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', PartnerStatus::Approved);
    }

    /**
     * Great-circle distance in kilometres from this partner to a point.
     * Returns null when either side lacks coordinates.
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371.0;

        $latDelta = deg2rad($latitude - $this->latitude);
        $lngDelta = deg2rad($longitude - $this->longitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) * sin($lngDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
