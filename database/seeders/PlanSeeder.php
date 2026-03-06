<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // BGITAL HOME
            ['category' => 'HOGAR', 'name' => 'BASIC 100 Mbps', 'price' => 399, 'speed' => '100 Mbps'],
            ['category' => 'HOGAR', 'name' => 'STANDARD 200 Mbps', 'price' => 449, 'speed' => '200 Mbps'],
            ['category' => 'HOGAR', 'name' => 'HIGH 300 Mbps', 'price' => 599, 'speed' => '300 Mbps'],
            ['category' => 'HOGAR', 'name' => 'PREMIUM 500 Mbps', 'price' => 695, 'speed' => '500 Mbps'],
            ['category' => 'HOGAR', 'name' => 'VIP 1000 Mbps', 'price' => 945, 'speed' => '1000 Mbps'],

            // BGITAL BUSINESS
            ['category' => 'NEGOCIO', 'name' => 'BASIC 100 Mbps', 'price' => 549, 'speed' => '100 Mbps'],
            ['category' => 'NEGOCIO', 'name' => 'STANDARD 200 Mbps', 'price' => 649, 'speed' => '200 Mbps'],
            ['category' => 'NEGOCIO', 'name' => 'PREMIUM 500 Mbps', 'price' => 749, 'speed' => '500 Mbps'],
            ['category' => 'NEGOCIO', 'name' => 'VIP 1000 Mbps', 'price' => 899, 'speed' => '1000 Mbps'],

            // BGITAL PYME
            ['category' => 'PYME', 'name' => 'BASIC 300 Mbps', 'price' => 1199, 'speed' => '300 Mbps'],
            ['category' => 'PYME', 'name' => 'STANDARD 500 Mbps', 'price' => 1399, 'speed' => '500 Mbps'],
            ['category' => 'PYME', 'name' => 'PREMIUM 1000 Mbps', 'price' => 1899, 'speed' => '1000 Mbps'],

            // BGITAL DEDICADO
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 50 Mbps', 'price' => 0, 'speed' => '50 Mbps', 'description' => 'A NEGOCIAR'],
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 100 Mbps', 'price' => 0, 'speed' => '100 Mbps', 'description' => 'A NEGOCIAR'],
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 150 Mbps', 'price' => 0, 'speed' => '150 Mbps', 'description' => 'A NEGOCIAR'],
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 200 Mbps', 'price' => 0, 'speed' => '200 Mbps', 'description' => 'A NEGOCIAR'],
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 300 Mbps', 'price' => 0, 'speed' => '300 Mbps', 'description' => 'A NEGOCIAR'],
            ['category' => 'DEDICADO', 'name' => 'DEDICADO 500 Mbps', 'price' => 0, 'speed' => '500 Mbps', 'description' => 'A NEGOCIAR'],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
