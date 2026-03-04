<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFlow extends Model
{
    protected $fillable = [
        'category',
        'trigger_keywords',
        'response_text',
        'response_type', // text, buttons, handoff, list, ticket_creation, troubleshooting
        'response_buttons',
        'media_path',
        'media_type',
        'is_active',
        'sort_order',
        'follow_up_to',
    ];

    public function followUp()
    {
        return $this->belongsTo(BotFlow::class , 'follow_up_to');
    }

    public function nextSteps()
    {
        return $this->hasMany(BotFlow::class , 'follow_up_to');
    }

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'response_buttons' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
