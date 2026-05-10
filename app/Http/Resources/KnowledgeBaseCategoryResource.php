<?php

namespace App\Http\Resources;

use App\Models\KnowledgeBaseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KnowledgeBaseCategory
 */
class KnowledgeBaseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'slug' => $this->slug,
            'description' => $this->getTranslations('description'),
            'sort_order' => $this->sort_order,
            'articles_count' => (int) ($this->resource->articles_count ?? 0),
            'articles' => KnowledgeBaseArticleResource::collection($this->whenLoaded('publishedArticles')),
        ];
    }
}
