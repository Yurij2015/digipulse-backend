<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'configuration_id',
        'status',
        'response_time_ms',
        'error_message',
        'metadata',
        'checked_at',
        'scheduled_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Site, CheckResult>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<SiteCheckConfiguration, CheckResult>
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(SiteCheckConfiguration::class, 'configuration_id');
    }
}
