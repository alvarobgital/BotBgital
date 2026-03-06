<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFlowStep extends Model
{
    protected $fillable = [
        'bot_flow_id', 'step_key', 'message_text', 'response_type',
        'options', 'action_type', 'action_config', 'next_step_default',
        'input_validation', 'retry_limit', 'is_entry_point', 'sort_order',
        'media_path', 'media_type'
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'action_config' => 'array',
            'is_entry_point' => 'boolean',
        ];
    }

    public function flow()
    {
        return $this->belongsTo(BotFlow::class , 'bot_flow_id');
    }
}
