<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function services()
    {
        return $this->hasMany(CustomerService::class);
    }

    /**
     * Check if at least one service is active
     */
    public function hasActiveService(): bool
    {
        return $this->services()->where('is_active', true)->exists();
    }
}
