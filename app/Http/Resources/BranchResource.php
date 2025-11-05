<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'detail_address' => $this->detail_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'working_hours' => $this->working_hours,
            'price_per_kg' => $this->price_per_kg,
            'image_url' => $this->image_url,
            'pickup_gojek' => $this->pickup_gojek,
            'pickup_grab' => $this->pickup_grab,
            'pickup_free' => $this->pickup_free,
            'pickup_free_schedule' => $this->pickup_free_schedule,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
