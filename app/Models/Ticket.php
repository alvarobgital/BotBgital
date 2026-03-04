<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'contact_id',
        'status',
        'priority',
        'subject',
        'description',
        'resolution_notes',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
