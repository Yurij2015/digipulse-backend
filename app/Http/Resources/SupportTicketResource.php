<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
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
            'subject' => $this->subject,
            'message' => $this->message,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'messages' => SupportTicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
