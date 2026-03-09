<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BotFlowStep;
use App\Models\BotFlow;

$res = [];

// 1. Get Prospect Flow
$flow = BotFlow::where('slug', 'prospect')->first();
if (!$flow) {
    echo "Prospect flow not found!";
    exit;
}

// 2. Delete existing steps
BotFlowStep::where('bot_flow_id', $flow->id)->delete();

// 3. Insert new steps as exactly requested by user
$steps = [
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'ask_cp',
        'message_text' => "📍 Para verificar la cobertura en tu zona, envíanos tu *Código Postal* (5 dígitos):",
        'response_type' => 'text',
        'input_validation' => 'zip_code',
        'action_type' => 'check_coverage',
        'is_entry_point' => true,
        'sort_order' => 1
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'confirm_neighborhood',
        'message_text' => "¡Tenemos cobertura en tu código postal! 🚀\n\n📍 CP: {{zip_code}}\n🏘️ Zonas cubiertas: {{coverage_zones}}\n\n⚠️ ¿Tu colonia aparece en la lista anterior?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'yes_colonia', 'title' => '✅ Sí, mi colonia está', 'next_step' => 'coverage_success'],
            ['id' => 'no_colonia', 'title' => '❌ No está', 'next_step' => 'select_service_type'],
        ],
        'sort_order' => 2
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'coverage_success',
        'message_text' => "¡Excelente! 🎉 Tienes cobertura confirmada.\n\n¿Qué te gustaría hacer ahora?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'opt_plans', 'title' => 'Ver Planes', 'next_flow' => 'client_type'],
            ['id' => 'opt_agent', 'title' => 'Hablar con Asesor', 'action' => 'escalate_agent'],
        ],
        'sort_order' => 3
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'select_service_type',
        'message_text' => "Entendido. Para poder brindarte la alternativa correcta, ¿El servicio de internet que buscas es para tu *Hogar* o para una *Empresa*?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'opt_home', 'title' => '🏠 Para Hogar', 'next_step' => 'no_coverage_home'],
            ['id' => 'opt_business', 'title' => '🏢 Para Empresa', 'next_step' => 'escalate_empresa'],
        ],
        'sort_order' => 4
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'no_coverage_home',
        'message_text' => "😔 Lamentamos mucho no tener cobertura en tu zona para servicio residencial en este momento.\n\nTe recomendamos estar pendiente de nuestras redes sociales para futuras expansiones.",
        'response_type' => 'action_only',
        'action_type' => 'close_conversation',
        'sort_order' => 5
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'escalate_empresa',
        'message_text' => "¡Excelente! Para servicios empresariales o dedicados, podemos evaluar la viabilidad técnica para extender nuestra infraestructura.\n\nHe notificado a un *Asesor Comercial* para que se ponga en contacto contigo y revisemos tu caso.",
        'response_type' => 'action_only',
        'action_type' => 'escalate_agent',
        'action_config' => ['reason' => 'Empresa fuera de cobertura inicial (viabilidad).'],
        'sort_order' => 6
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'no_coverage',
        'message_text' => "📍 *Zona en Expansión*\n\nActualmente no tenemos red activa en CP *{{zip_code}}*.\n\nPara evaluar la viabilidad, ¿el servicio sería para?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'nc_home', 'title' => 'Casa 🏠', 'next_step' => 'no_cov_home'],
            ['id' => 'nc_business', 'title' => 'Empresa 🏢', 'next_step' => 'no_cov_business'],
        ],
        'sort_order' => 7
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'no_cov_home',
        'message_text' => "😔 Lamentamos no tener cobertura en tu zona para servicio residencial.\n\nPor el momento no contamos con infraestructura en dicho Código Postal.",
        'response_type' => 'text',
        'action_type' => 'close_conversation',
        'sort_order' => 8
    ],
    [
        'bot_flow_id' => $flow->id,
        'step_key' => 'no_cov_business',
        'message_text' => "🏢 *¡Podemos evaluarlo!*\n\nPara empresas tenemos la posibilidad de extender nuestra red de fibra óptica.\n\nHe notificado a un asesor para revisar viabilidad.",
        'response_type' => 'text',
        'action_type' => 'escalate_agent',
        'action_config' => ['reason' => 'Evaluación de viabilidad corporativa.'],
        'sort_order' => 9
    ]
];

foreach ($steps as $s) {
    BotFlowStep::create($s);
}

echo "Prospect Flow Re-seeded successfully!\n";
