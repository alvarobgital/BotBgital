<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
        ['email' => 'admin@bgital.mx'],
        [
            'name' => 'Administrador Bgital',
            'password' => Hash::make('Bgital2026!'),
            'role' => 'admin',
            'is_active' => true,
        ]
        );
    }
}
