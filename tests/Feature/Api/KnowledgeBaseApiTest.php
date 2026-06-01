<?php

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->frontendKey = config('app.frontend_key');
});

test('knowledge base lists categories with article counts', function () {
    $this->seed(KnowledgeBaseSeeder::class);

    $this->getJson(route('v1.knowledge-base.categories'), ['X-Frontend-Key' => $this->frontendKey])
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'articles_count']]]);
});

test('knowledge base returns category with published articles', function () {
    $this->seed(KnowledgeBaseSeeder::class);

    $this->getJson(route('v1.knowledge-base.category', 'monitoring-checks'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'monitoring-checks')
        ->assertJsonStructure(['data' => ['articles']]);
});

test('knowledge base returns published article by slug', function () {
    $this->seed(KnowledgeBaseSeeder::class);

    $this->getJson(route('v1.knowledge-base.article', 'http-status-check'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'http-status-check');
});

test('knowledge base returns 404 for unknown category slug', function () {
    $this->getJson(route('v1.knowledge-base.category', 'missing'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertNotFound();
});

test('knowledge base returns 404 for unpublished article', function () {
    $category = KnowledgeBaseCategory::create([
        'slug' => 'drafts',
        'name' => ['en' => 'Drafts'],
        'description' => ['en' => 'Draft articles'],
        'sort_order' => 99,
    ]);

    KnowledgeBaseArticle::create([
        'knowledge_base_category_id' => $category->id,
        'slug' => 'draft-article',
        'title' => ['en' => 'Draft'],
        'excerpt' => ['en' => 'Not published yet'],
        'content' => ['en' => 'Body'],
        'sort_order' => 1,
        'published_at' => null,
    ]);

    $this->getJson(route('v1.knowledge-base.article', 'draft-article'), [
        'X-Frontend-Key' => $this->frontendKey,
    ])->assertNotFound();
});
