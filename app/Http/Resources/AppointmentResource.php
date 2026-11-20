<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Appointment */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'time' => substr((string) $this->time, 0, 5),
            'status' => $this->status->value,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'walk_in_name' => $this->walk_in_name,
            'barber' => new UserResource($this->whenLoaded('barber')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
