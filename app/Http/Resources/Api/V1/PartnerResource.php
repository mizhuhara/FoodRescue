<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Partner $partner */
        $partner = $this->resource;

        return [
            'id' => $partner->id,
            'business_name' => $partner->business_name,
            'logo' => $partner->logo,
            'address' => $partner->address,
            'latitude' => $partner->latitude,
            'longitude' => $partner->longitude,
        ];
    }
}
