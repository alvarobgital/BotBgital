<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'bot_enabled' => 'true',
            'empresa_nombre' => 'Bgital',
            'empresa_web' => 'https://bgital.mx',
            'horario_atencion' => 'Lunes a Viernes 9:00 - 18:00',
            'telegram_bot_token' => '',
            'telegram_notify_group_id' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
