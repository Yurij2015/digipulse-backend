<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Site extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'name', 'url', 'update_interval', 'is_active'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configurations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SiteCheckConfiguration::class);
    }

    public function checks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Check::class, SiteCheckConfiguration::class);
    }
}
