<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function checks(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->latestOfMany('checked_at');
    }

    public function latestHttpCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'http'));
        });
    }

    public function latestSslCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'ssl'));
        });
    }

    public function latestPingCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereHas('configuration.checkType', fn ($q) => $q->where('slug', 'ping'));
        });
    }
}
