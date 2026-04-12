<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'url', 'update_interval', 'is_active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(SiteCheckConfiguration::class);
    }

    public function checks(): HasManyThrough
    {
        return $this->hasManyThrough(CheckResult::class, SiteCheckConfiguration::class);
    }
}
