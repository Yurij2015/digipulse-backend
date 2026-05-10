<?php

namespace App\Http\Resources;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin KnowledgeBaseArticle
 */
class KnowledgeBaseArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coverUrl = $this->cover_image
            ? Storage::disk('minio')->url($this->cover_image)
            : null;

        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'slug' => $this->slug,
            'excerpt' => $this->getTranslations('excerpt'),
            'content' => $this->when($this->resource->relationLoaded('category') || $request->routeIs('knowledge-base.article'), $this->getTranslations('content')),
            'cover_image' => $coverUrl,
            'meta' => [
                'title' => $this->meta_title ?: null,
                'description' => $this->meta_description ?: null,
                'og_image' => $coverUrl,
            ],
            'category' => new KnowledgeBaseCategoryResource($this->whenLoaded('category')),
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at,
        ];
    }
}
