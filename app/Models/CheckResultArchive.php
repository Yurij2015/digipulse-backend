<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResultArchive extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'configuration_id',
        'year',
        'week',
        'data',
        'size_bytes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<Site, CheckResultArchive>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<SiteCheckConfiguration, CheckResultArchive>
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(SiteCheckConfiguration::class, 'configuration_id');
    }
}
