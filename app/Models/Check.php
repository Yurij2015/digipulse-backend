<?php
 
 namespace App\Models;
 
 use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Model;
 
 class Check extends Model
 {
     use HasFactory;
 
     protected $fillable = ['site_check_configuration_id', 'is_successful', 'response_time', 'results', 'error_message'];
 
     protected $casts = [
         'is_successful' => 'boolean',
         'results' => 'array',
     ];
 
     public function configuration(): \Illuminate\Database\Eloquent\Relations\BelongsTo
     {
         return $this->belongsTo(SiteCheckConfiguration::class, 'site_check_configuration_id');
     }
 }
