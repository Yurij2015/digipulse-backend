<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'project_id', 'name', 'url', 'update_interval', 'is_active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
            $query->whereIn('configuration_id', $this->configIdsForType('http'));
        });
    }

    public function latestSslCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereIn('configuration_id', $this->configIdsForType('ssl'));
        });
    }

    public function latestPingCheck(): HasOne
    {
        return $this->hasOne(CheckResult::class)->ofMany([
            'checked_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereIn('configuration_id', $this->configIdsForType('ping'));
        });
    }

    private function configIdsForType(string $slug): Builder
    {
        return DB::table('site_check_configurations')
            ->join('check_types', 'check_types.id', '=', 'site_check_configurations.check_type_id')
            ->where('check_types.slug', $slug)
            ->select('site_check_configurations.id');
    }
}
