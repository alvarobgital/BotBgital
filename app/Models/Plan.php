<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'description',
        'price',
        'speed',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
    ];
}
