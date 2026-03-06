<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageArea extends Model
{
    protected $fillable = ['city', 'neighborhood', 'zip_code', 'streets', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
