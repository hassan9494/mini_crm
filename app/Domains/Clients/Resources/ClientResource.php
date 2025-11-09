<?php

namespace App\Domains\Clients\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'assigned_rep' => $this->whenLoaded('assignedRep', function () {
                return [
                    'id' => $this->assignedRep?->id,
                    'name' => $this->assignedRep?->name,
                    'email' => $this->assignedRep?->email,
                ];
            }),
            'last_communication_date' => optional($this->last_communication_date)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
