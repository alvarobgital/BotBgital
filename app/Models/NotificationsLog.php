<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationsLog extends Model
{
    protected $table = 'notifications_log';

    protected $fillable = [
        'type', 'recipient', 'subject', 'body', 'conversation_id', 'sent_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
