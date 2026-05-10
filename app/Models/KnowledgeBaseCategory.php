<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class KnowledgeBaseCategory extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class)->orderBy('sort_order');
    }

    public function publishedArticles(): HasMany
    {
        return $this->articles()->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
