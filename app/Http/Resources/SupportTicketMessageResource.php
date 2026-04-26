<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketMessageResource extends JsonResource
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
            'message' => $this->message,
            'is_admin_reply' => (bool) $this->is_admin_reply,
            'created_at' => $this->created_at->toISOString(),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
            ],
        ];
    }
}
