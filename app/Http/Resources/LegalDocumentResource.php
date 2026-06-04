<?php

namespace App\Http\Resources;

use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LegalDocument
 */
class LegalDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->getTranslations('title'),
            'content' => $this->getTranslations('content'),
            'published_at' => $this->published_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
