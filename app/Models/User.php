<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasOne<Partner, $this>
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    /**
     * @return <float>|null
     */
    public function distanceToPartner(Partner $partner): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }
        if ($partner->latitude === null || $partner->longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($partner->latitude - $this->latitude);
        $lngDelta = deg2rad($partner->longitude - $this->longitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($this->latitude)) * cos(deg2rad($partner->latitude))
                * sin($lngDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
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
