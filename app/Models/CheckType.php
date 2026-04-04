<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class CheckType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'icon', 'is_active'];

    public function configurations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SiteCheckConfiguration::class);
    }
}
