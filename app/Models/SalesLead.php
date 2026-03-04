<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLead extends Model
{
    protected $fillable = [
        'contact_id', 'conversation_id', 'plan_interest', 'rfc', 'curp',
        'address_proof_path', 'status',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
