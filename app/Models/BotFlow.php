<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFlow extends Model
{
    protected $fillable = [
        'category',
        'trigger_keywords',
        'response_text',
        'response_type',
        'response_buttons',
        'media_path',
        'media_type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'response_buttons' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
