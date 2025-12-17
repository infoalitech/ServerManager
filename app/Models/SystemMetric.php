<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'ram_details',
        'disk_details'
    ];

    protected $casts = [
        'ram_details' => 'array',
        'disk_details' => 'array',
    ];
}