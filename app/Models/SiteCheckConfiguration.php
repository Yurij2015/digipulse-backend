<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class SiteCheckConfiguration extends Model
{
    use HasFactory;
    protected $fillable = ['site_id', 'check_type_id', 'params', 'is_active', 'last_status', 'last_checked_at'];

    protected $casts = [
        'params' => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function site(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function checkType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CheckType::class);
    }

    public function checks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Check::class, 'site_check_configuration_id');
    }
}
