<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BotFlow;

class BotFlowSeeder extends Seeder
{
    public function run(): void
    {
        $flows = [
            [
                'category' => 'saludo',
                'trigger_keywords' => ['hola', 'buenas', 'buenos', 'hi', 'hello', 'inicio', 'menu', 'menú'],
                'response_text' => '¡Hola! Bienvenido a Bgital 👋 Somos tu empresa de telecomunicaciones de confianza. ¿En qué te puedo ayudar hoy?',
                'response_type' => 'buttons',
                'response_buttons' => [
                    ['id' => 'btn_planes', 'title' => 'Ver planes'],
                    ['id' => 'btn_info', 'title' => 'Información'],
                    ['id' => 'btn_asesor', 'title' => 'Hablar con asesor'],
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category' => 'planes',
                'trigger_keywords' => ['planes', 'plan', 'precio', 'precios', 'costo', 'costos', 'paquete', 'paquetes', 'btn_planes'],
                'response_text' => "📱 *Nuestros Planes de Telecomunicaciones:*\n\n🔹 *Plan Básico* — Internet + Telefonía\n🔹 *Plan Plus* — Internet de alta velocidad + TV\n🔹 *Plan Premium* — Todo incluido con velocidad máxima\n\nContáctanos para conocer precios y disponibilidad en tu zona.\n\n¿Te gustaría más información sobre algún plan?",
                'response_type' => 'buttons',
                'response_buttons' => [
                    ['id' => 'btn_mas_info', 'title' => 'Quiero más info'],
                    ['id' => 'btn_asesor', 'title' => 'Hablar con asesor'],
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category' => 'info',
                'trigger_keywords' => ['info', 'información', 'informacion', 'quienes son', 'bgital', 'empresa', 'btn_info', 'btn_mas_info'],
                'response_text' => "ℹ️ *Sobre Bgital*\n\nSomos una empresa mexicana de telecomunicaciones comprometida con brindarte la mejor conectividad.\n\n🌐 Visita nuestro sitio web: https://bgital.mx\n📞 Atención personalizada disponible\n📍 Cobertura en crecimiento constante\n\n¿En qué más te podemos ayudar?",
                'response_type' => 'text',
                'response_buttons' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'category' => 'horarios',
                'trigger_keywords' => ['horario', 'hora', 'horarios', 'cuando atienden', 'abierto', 'disponible', 'atienden'],
                'response_text' => "🕐 *Horario de Atención Bgital*\n\n📅 Lunes a Viernes: 9:00 AM - 6:00 PM\n📅 Sábados: 9:00 AM - 2:00 PM\n📅 Domingos: Cerrado\n\nFuera de horario, nuestro bot seguirá atendiéndote. Para asuntos urgentes, déjanos un mensaje y te contactaremos al iniciar el siguiente día hábil.",
                'response_type' => 'text',
                'response_buttons' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'category' => 'soporte',
                'trigger_keywords' => ['asesor', 'agente', 'persona', 'humano', 'ayuda', 'soporte', 'btn_asesor', 'problema', 'falla', 'no funciona'],
                'response_text' => 'Perfecto, en breve un asesor de Bgital te atenderá. ¡Gracias por tu paciencia! 🙏',
                'response_type' => 'handoff',
                'response_buttons' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'category' => 'web',
                'trigger_keywords' => ['sitio', 'web', 'pagina', 'página', 'sitio web', 'link', 'enlace'],
                'response_text' => "🌐 Visita nuestro sitio web oficial:\n👉 https://bgital.mx\n\nAhí encontrarás toda la información sobre nuestros servicios, cobertura y promociones.",
                'response_type' => 'text',
                'response_buttons' => null,
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($flows as $flow) {
            BotFlow::updateOrCreate(
            ['category' => $flow['category']],
                $flow
            );
        }

        // --- Ejemplo de Soporte Avanzado (Telecomunicaciones) ---

        // 1. Detección inicial
        $step1 = BotFlow::updateOrCreate(
        ['category' => 'falla_internet'],
        [
            'trigger_keywords' => ['no tengo internet', 'no hay internet', 'falla internet', 'internet lento', 'sin red'],
            'response_text' => "📡 *Diagnóstico de Red Bgital*\n\nLamento que tengas problemas. Vamos a intentar solucionarlo rápidamente.\n\n*Paso 1:* Por favor, verifica que tu modem esté encendido y que el cable de fibra óptica (el cable blanco delgado) no esté doblado o roto.\n\n¿Ya realizaste esta verificación?",
            'response_type' => 'troubleshooting',
            'is_active' => true,
            'sort_order' => 10,
        ]
        );

        // 2. Paso 2 (Reinicio)
        $step2 = BotFlow::updateOrCreate(
        ['category' => 'reinicio_modem'],
        [
            'trigger_keywords' => [],
            'response_text' => "🔄 *Paso 2: Reinicio de Equipo*\n\nDesconecta tu modem de la corriente eléctrica por 30 segundos y vuelve a conectarlo. Espera 2 minutos a que todas las luces se estabilicen.\n\n¿Esto solucionó tu problema?",
            'response_type' => 'troubleshooting',
            'follow_up_to' => $step1->id,
            'is_active' => true,
            'sort_order' => 11,
        ]
        );

        // Vinculamos el paso 1 al paso 2 como seguimiento
        $step1->update(['follow_up_to' => $step2->id]);

        // El motor del bot detectará que después del Step 2 no hay más flujos 
        // y ofrecerá crear el ticket automáticamente.

        // 3. Consulta de Cobertura
        BotFlow::updateOrCreate(
        ['category' => 'consulta_cobertura'],
        [
            'trigger_keywords' => ['cobertura', 'donde llegan', 'hay servicio', 'tienen red', 'llegada', 'zona'],
            'response_text' => "🔍 *Verificador de Cobertura Bgital*\n\nNuestra red de fibra óptica se expande cada día. Por favor, dime tu *Código Postal* de 5 dígitos para verificar la disponibilidad en tu calle:",
            'response_type' => 'coverage_checker',
            'response_data' => [
                'zip_codes' => ['06000', '06140', '06700', '52760', '52763', '52764'] // CPs de ejemplo
            ],
            'is_active' => true,
            'sort_order' => 15,
        ]
        );
    }
}
