<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'configuration_id' => data_get($this->resource, 'configuration_id'),
            'status' => data_get($this->resource, 'status'),
            'response_time_ms' => data_get($this->resource, 'response_time_ms'),
            'error_message' => data_get($this->resource, 'error_message'),
            'metadata' => data_get($this->resource, 'metadata'),
            'checked_at' => data_get($this->resource, 'checked_at') instanceof Carbon
                ? data_get($this->resource, 'checked_at')->toDateTimeString()
                : data_get($this->resource, 'checked_at'),
        ];
    }
}
