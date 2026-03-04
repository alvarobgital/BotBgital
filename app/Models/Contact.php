<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket; // Added for tickets relationship

class Contact extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'platform',
        'type', // Added 'type' to fillable
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function latestConversation()
    {
        return $this->hasOne(Conversation::class)->latestOfMany();
    }

    public function salesLeads()
    {
        return $this->hasMany(SalesLead::class);
    }
}
