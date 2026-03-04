<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'contact_id', 'assigned_agent_id', 'status', 'bot_state', 'bot_state_data', 'started_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'bot_state_data' => 'array',
        ];
    }


    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class , 'assigned_agent_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function salesLeads()
    {
        return $this->hasMany(SalesLead::class);
    }

    public function notificationsLog()
    {
        return $this->hasMany(NotificationsLog::class);
    }
}
