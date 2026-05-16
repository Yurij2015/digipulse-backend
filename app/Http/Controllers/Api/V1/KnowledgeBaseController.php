<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Http\Resources\KnowledgeBaseCategoryResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class KnowledgeBaseController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = KnowledgeBaseCategory::orderBy('sort_order')
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();

        return KnowledgeBaseCategoryResource::collection($categories);
    }

    public function category(string $slug): KnowledgeBaseCategoryResource|JsonResponse
    {
        $category = KnowledgeBaseCategory::where('slug', $slug)
            ->with(['publishedArticles'])
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->first();

        if (! $category) {
            return response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        return new KnowledgeBaseCategoryResource($category);
    }

    public function article(string $slug): KnowledgeBaseArticleResource|JsonResponse
    {
        $article = KnowledgeBaseArticle::published()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if (! $article) {
            return response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        return new KnowledgeBaseArticleResource($article);
    }
}
