<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'account_number',
        'plan_name',
        'label',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
