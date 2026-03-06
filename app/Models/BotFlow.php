<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFlow extends Model
{
    protected $fillable = [
        'slug', 'category', 'flow_type', 'description',
        'trigger_keywords', 'response_text', 'response_type',
        'response_buttons', 'media_path', 'media_type',
        'is_active', 'is_system_flow', 'sort_order', 'flow_priority',
        'follow_up_to', 'response_data',
    ];

    protected function casts(): array
    {
        return [
            'trigger_keywords' => 'array',
            'response_buttons' => 'array',
            'response_data' => 'array',
            'is_active' => 'boolean',
            'is_system_flow' => 'boolean',
        ];
    }

    public function steps()
    {
        return $this->hasMany(BotFlowStep::class)->orderBy('sort_order');
    }

    public function entryStep()
    {
        return $this->hasOne(BotFlowStep::class)->where('is_entry_point', true);
    }

    public function followUp()
    {
        return $this->belongsTo(BotFlow::class , 'follow_up_to');
    }

    public function nextSteps()
    {
        return $this->hasMany(BotFlow::class , 'follow_up_to');
    }
}
