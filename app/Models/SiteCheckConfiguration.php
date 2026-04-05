<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteCheckConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['site_id', 'check_type_id', 'params', 'is_active', 'last_status', 'last_checked_at'];

    protected $casts = [
        'params' => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function checkType(): BelongsTo
    {
        return $this->belongsTo(CheckType::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class, 'site_check_configuration_id');
    }
}
