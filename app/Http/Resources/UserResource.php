<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'google_nickname' => $this->google_nickname,
            'google_avatar' => $this->google_avatar,
            'google_id' => $this->google_id,
            'telegram_chat_id' => $this->telegram_chat_id,
            'notify_email' => $this->notify_email,
            'notify_telegram' => $this->notify_telegram,
            'email_verified_at' => $this->email_verified_at,
            'is_verified' => $this->email_verified_at !== null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
