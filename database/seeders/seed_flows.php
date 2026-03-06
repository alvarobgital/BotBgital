<?php

use App\Models\BotFlow;
use App\Models\BotFlowStep;

BotFlowStep::query()->delete();
BotFlow::query()->delete();

// ═══════════════════════════════════════
//  MAIN FLOWS
// ═══════════════════════════════════════

// 1. BIENVENIDA
$welcome = BotFlow::create([
    'slug' => 'welcome', 'category' => 'Bienvenida', 'flow_type' => 'main',
    'description' => 'Primer mensaje al iniciar conversación',
    'trigger_keywords' => ['hola', 'buenas', 'buenos', 'hi', 'hello', 'inicio', 'menu', 'menú', 'empezar'],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 1, 'flow_priority' => 100,
]);
BotFlowStep::create([
    'bot_flow_id' => $welcome->id, 'step_key' => 'saludo', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "🌐 ¡Bienvenido a *BGITAL* Telecomunicaciones!\nSomos tu empresa de *Internet de Fibra Óptica*.\n\n¿Cómo podemos ayudarte hoy?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'opt_client', 'title' => 'Soy Cliente', 'next_flow' => 'client_validation'],
        ['id' => 'opt_prospect', 'title' => 'Posible Cliente', 'next_flow' => 'prospect'],
    ],
]);

// 2. VALIDACIÓN CLIENTE
$clientVal = BotFlow::create([
    'slug' => 'client_validation', 'category' => 'Validación Cliente', 'flow_type' => 'main',
    'description' => 'Validar identidad del cliente con nombre y cuenta',
    'trigger_keywords' => ['soy cliente', 'mi cuenta', 'mi servicio'],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 2, 'flow_priority' => 90,
]);
BotFlowStep::create([
    'bot_flow_id' => $clientVal->id, 'step_key' => 'ask_name', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "🔐 Para brindarte atención personalizada necesitamos validar tu identidad.\n\n✏️ Escribe el *nombre completo del titular* del servicio:",
    'response_type' => 'input', 'input_validation' => 'name', 'retry_limit' => 3,
    'next_step_default' => 'ask_account',
]);
BotFlowStep::create([
    'bot_flow_id' => $clientVal->id, 'step_key' => 'ask_account', 'sort_order' => 2,
    'message_text' => "Gracias, *{{ask_name}}*.\n\nAhora ingresa tu *Número de Cuenta*:\n📌 _Lo encuentras en tu recibo o contrato (ej. BG-100)_",
    'response_type' => 'input', 'input_validation' => 'account_number', 'retry_limit' => 3,
    'next_step_default' => 'validate', 'action_type' => 'validate_client',
]);
BotFlowStep::create([
    'bot_flow_id' => $clientVal->id, 'step_key' => 'validate', 'sort_order' => 3,
    'message_text' => "👋 ¡Hola, *{{customer_name}}*!\n\n🔢 Cuenta: *{{account_number}}*\n📦 Plan: *{{plan_name}}*\n✅ Estado: Activo\n\n¿En qué te podemos ayudar?",
    'response_type' => 'list',
    'options' => [
        ['id' => 'cl_fault', 'title' => 'Reportar Falla', 'description' => 'Tengo problemas con mi internet', 'next_flow' => 'technical_support'],
        ['id' => 'cl_info', 'title' => 'Info de mi Servicio', 'description' => 'Ver datos de mi contrato', 'next_step' => 'show_info'],
        ['id' => 'cl_agent', 'title' => 'Hablar con Asesor', 'description' => 'Atención personalizada', 'action' => 'escalate_agent'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $clientVal->id, 'step_key' => 'show_info', 'sort_order' => 4,
    'message_text' => "📋 *Información de tu Servicio*\n\n👤 Titular: *{{customer_name}}*\n🔢 Cuenta: *{{account_number}}*\n📦 Plan: *{{plan_name}}*\n✅ Estado: Activo\n\n¿Necesitas algo más?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'cl_agent', 'title' => 'Hablar con Asesor', 'action' => 'escalate_agent'],
        ['id' => 'menu', 'title' => 'Menú Principal', 'next_flow' => 'welcome'],
    ],
]);

// 3. SOPORTE TÉCNICO
$support = BotFlow::create([
    'slug' => 'technical_support', 'category' => 'Soporte Técnico', 'flow_type' => 'main',
    'description' => 'Diagnóstico y soluciones guiadas para fallas',
    'trigger_keywords' => ['falla', 'problema', 'no funciona', 'soporte', 'no jala', 'sin internet', 'lento'],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 3, 'flow_priority' => 85,
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'select_fault', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "🔧 Lamentamos que tengas problemas.\n\n¿Cuál es la falla que presentas?",
    'response_type' => 'list',
    'options' => [
        ['id' => 'f_no_net', 'title' => 'Sin Internet', 'description' => 'No tengo conexión', 'next_step' => 'fix_no_net'],
        ['id' => 'f_slow', 'title' => 'Internet Lento', 'description' => 'Velocidad baja', 'next_step' => 'fix_slow'],
        ['id' => 'f_intermit', 'title' => 'Intermitencia', 'description' => 'Se cae y regresa', 'next_step' => 'fix_intermit'],
        ['id' => 'f_router', 'title' => 'Problema con Router', 'description' => 'Equipo no enciende', 'next_step' => 'fix_router'],
        ['id' => 'f_other', 'title' => 'Otro Problema', 'description' => 'No está en la lista', 'action' => 'escalate_agent'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'fix_no_net', 'sort_order' => 2,
    'message_text' => "🔧 *Sin Internet — Guía de Solución*\n\n*Paso 1:* Verifica el cable de fibra óptica (blanco) conectado al ONT/Modem.\n*Paso 2:* Desconecta la energía del modem *60 segundos* y reconéctalo.\n*Paso 3:* Espera 3 minutos a que reinicie.\n*Paso 4:* Revisa que las luces *PON/LOS* estén fijas (no rojo).\n\n¿Se solucionó?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'ts_ok', 'title' => 'Ya Funciona ✅', 'next_step' => 'resolved'],
        ['id' => 'ts_no', 'title' => 'Sigue Fallando ❌', 'next_step' => 'not_resolved'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'fix_slow', 'sort_order' => 3,
    'message_text' => "🔧 *Internet Lento — Guía de Solución*\n\n*Paso 1:* Desconecta repetidores WiFi.\n*Paso 2:* Conecta un dispositivo *por cable Ethernet*.\n*Paso 3:* Haz test en *fast.com*\n*Paso 4:* Si por cable es correcto → problema WiFi. Acércate al modem.\n\n¿Mejoró tu velocidad?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'ts_ok', 'title' => 'Ya Funciona ✅', 'next_step' => 'resolved'],
        ['id' => 'ts_no', 'title' => 'Sigue Fallando ❌', 'next_step' => 'not_resolved'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'fix_intermit', 'sort_order' => 4,
    'message_text' => "🔧 *Intermitencia — Guía de Solución*\n\n*Paso 1:* Verifica que la fibra no esté doblada ni prensada.\n*Paso 2:* Reinicia el modem (60 seg).\n*Paso 3:* Conecta por cable Ethernet.\n\n¿Se estabilizó tu conexión?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'ts_ok', 'title' => 'Ya Funciona ✅', 'next_step' => 'resolved'],
        ['id' => 'ts_no', 'title' => 'Sigue Fallando ❌', 'next_step' => 'not_resolved'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'fix_router', 'sort_order' => 5,
    'message_text' => "🔧 *Problema con Router — Guía*\n\n*Paso 1:* Verifica cable de energía conectado.\n*Paso 2:* Prueba otra toma de corriente.\n*Paso 3:* Si no enciende, necesita revisión técnica.\n\n¿Se resolvió?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'ts_ok', 'title' => 'Ya Funciona ✅', 'next_step' => 'resolved'],
        ['id' => 'ts_no', 'title' => 'Sigue Fallando ❌', 'next_step' => 'not_resolved'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'resolved', 'sort_order' => 10,
    'message_text' => "✅ ¡Excelente! Me alegra que se haya solucionado.\n\nGracias por comunicarte con *BGITAL*. Escribe *Menú* si necesitas algo más. 👋",
    'response_type' => 'text', 'action_type' => 'close_conversation',
]);
BotFlowStep::create([
    'bot_flow_id' => $support->id, 'step_key' => 'not_resolved', 'sort_order' => 11,
    'message_text' => "😔 Lamento que el problema persista.\n\nHe notificado a un *asesor de soporte técnico*. Te contactará para evaluar tu problema y agendar una visita técnica si es necesario.",
    'response_type' => 'text', 'action_type' => 'escalate_agent',
    'action_config' => ['reason' => 'Problema técnico no resuelto con soluciones guiadas'],
]);

// 4. PROSPECTO
$prospect = BotFlow::create([
    'slug' => 'prospect', 'category' => 'Prospecto', 'flow_type' => 'main',
    'description' => 'Verificar cobertura y opciones para posibles clientes',
    'trigger_keywords' => ['contratar', 'posible cliente', 'nuevo', 'quiero internet', 'informacion'],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 4, 'flow_priority' => 80,
]);
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'ask_cp', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "📍 Para verificar la cobertura en tu zona, envíanos tu *Código Postal* (5 dígitos):",
    'response_type' => 'input', 'input_validation' => 'zip_code', 'retry_limit' => 3,
    'next_step_default' => 'check_coverage', 'action_type' => 'check_coverage',
]);
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'check_coverage', 'sort_order' => 2,
    'message_text' => "✅ *¡Tenemos cobertura en tu zona!* 🚀\n\n📍 CP: {{zip_code}}\n🏘️ Zonas: {{coverage_zones}}\n\n¿Qué te gustaría hacer?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'opt_plans', 'title' => 'Ver Planes 📋', 'next_flow' => 'client_type'],
        ['id' => 'opt_web', 'title' => 'Sitio Web 🌐', 'next_step' => 'show_web'],
        ['id' => 'opt_agent', 'title' => 'Hablar con Asesor', 'action' => 'escalate_agent'],
    ],
]);
// ── NO COVERAGE: ask Casa o Empresa ──
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'no_coverage', 'sort_order' => 3,
    'message_text' => "📍 *Zona en Expansión*\n\nActualmente no tenemos red activa en CP *{{zip_code}}*.\n\nPara evaluar la viabilidad, ¿el servicio sería para?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'nc_home', 'title' => 'Casa 🏠', 'next_step' => 'no_cov_home'],
        ['id' => 'nc_business', 'title' => 'Empresa 🏢', 'next_step' => 'no_cov_business'],
    ],
]);
// Casa sin cobertura = finalizar (no tiene sentido construir para 1 casa)
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'no_cov_home', 'sort_order' => 4,
    'message_text' => "😔 Lamentamos no tener cobertura en tu zona para servicio residencial.\n\nPor el momento no contamos con infraestructura en CP *{{zip_code}}* para hogares.\n\nTe recomendamos estar pendiente de nuestras redes sociales para conocer nuevas zonas de cobertura.\n\n¡Gracias por tu interés en *BGITAL*! 👋",
    'response_type' => 'text', 'action_type' => 'close_conversation',
]);
// Empresa sin cobertura = contactar asesor (vale la pena construir)
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'no_cov_business', 'sort_order' => 5,
    'message_text' => "🏢 *¡Podemos evaluarlo!*\n\nPara empresas tenemos la posibilidad de extender nuestra red de fibra óptica.\n\nHe notificado a nuestro equipo comercial para evaluar la viabilidad en CP *{{zip_code}}*.\n\nUn asesor se pondrá en contacto contigo.",
    'response_type' => 'text', 'action_type' => 'escalate_agent',
    'action_config' => ['reason' => 'Empresa sin cobertura — evaluar viabilidad de extensión de red'],
]);
BotFlowStep::create([
    'bot_flow_id' => $prospect->id, 'step_key' => 'show_web', 'sort_order' => 6,
    'message_text' => "🌐 Visita nuestro sitio:\n\n👉 https://bgital.mx\n\n¿Algo más?",
    'response_type' => 'buttons',
    'options' => [['id' => 'menu', 'title' => 'Menú Principal', 'next_flow' => 'welcome']],
]);

// 5. TIPO DE CLIENTE
$clientType = BotFlow::create([
    'slug' => 'client_type', 'category' => 'Tipo de Cliente', 'flow_type' => 'main',
    'description' => 'Identificar si es para casa o empresa',
    'trigger_keywords' => ['planes', 'plan', 'precio', 'paquete', 'costo'],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 5, 'flow_priority' => 75,
]);
BotFlowStep::create([
    'bot_flow_id' => $clientType->id, 'step_key' => 'ask_type', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "🏠🏢 ¿Qué tipo de servicio te interesa?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'type_home', 'title' => 'Internet Casa 🏠', 'next_flow' => 'plans'],
        ['id' => 'type_business', 'title' => 'Internet Empresa 🏢', 'next_step' => 'business_size'],
    ],
]);
BotFlowStep::create([
    'bot_flow_id' => $clientType->id, 'step_key' => 'business_size', 'sort_order' => 2,
    'message_text' => "🏢 *Planes Empresariales*\n\n¿Qué tipo de negocio tienes?",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'biz_small', 'title' => 'Negocio Pequeño 🏪', 'next_flow' => 'plans'],
        ['id' => 'biz_medium', 'title' => 'Empresa Mediana 🏢', 'next_flow' => 'plans'],
        ['id' => 'biz_large', 'title' => 'Empresa Grande 💎', 'next_flow' => 'plans'],
    ],
]);

// 6. PLANES
$plans = BotFlow::create([
    'slug' => 'plans', 'category' => 'Planes', 'flow_type' => 'main',
    'description' => 'Mostrar categorías y planes disponibles',
    'trigger_keywords' => [],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 6, 'flow_priority' => 70,
]);
BotFlowStep::create([
    'bot_flow_id' => $plans->id, 'step_key' => 'show_categories', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "📋 *Planes BGITAL*\nIVA incluido, velocidad simétrica.\n\nSelecciona categoría:",
    'response_type' => 'action_only', 'action_type' => 'show_plan_categories',
    'next_step_default' => 'show_plans',
]);
BotFlowStep::create([
    'bot_flow_id' => $plans->id, 'step_key' => 'show_plans', 'sort_order' => 2,
    'message_text' => "📋 Selecciona el plan que te interesa:",
    'response_type' => 'action_only', 'action_type' => 'show_plans',
    'next_step_default' => 'confirm_plan',
]);
BotFlowStep::create([
    'bot_flow_id' => $plans->id, 'step_key' => 'confirm_plan', 'sort_order' => 3,
    'message_text' => "✅ *¡Excelente decisión!*\n\n📦 Plan: *{{selected_plan}}*\n📍 CP: {{zip_code}}\n\nHe registrado tu interés. Un asesor de ventas se comunicará contigo para coordinar la contratación e instalación.",
    'response_type' => 'buttons', 'action_type' => 'create_lead',
    'options' => [['id' => 'menu', 'title' => 'Menú Principal', 'next_flow' => 'welcome']],
]);

// 7. FALLBACK
$fallback = BotFlow::create([
    'slug' => 'fallback', 'category' => 'Fallback', 'flow_type' => 'main',
    'description' => 'Respuesta cuando el bot no entiende',
    'trigger_keywords' => [],
    'is_active' => true, 'is_system_flow' => true, 'sort_order' => 99, 'flow_priority' => 1,
]);
BotFlowStep::create([
    'bot_flow_id' => $fallback->id, 'step_key' => 'fallback_msg', 'is_entry_point' => true, 'sort_order' => 1,
    'message_text' => "🤔 No entendí tu mensaje.\n\nPuedes escribir:\n\n• *Cliente* — si ya tienes servicio\n• *Contratar* — para conocer planes\n• *Asesor* — para hablar con una persona\n• *Menú* — para reiniciar",
    'response_type' => 'buttons',
    'options' => [
        ['id' => 'opt_client', 'title' => 'Soy Cliente', 'next_flow' => 'client_validation'],
        ['id' => 'opt_prospect', 'title' => 'Contratar', 'next_flow' => 'prospect'],
        ['id' => 'opt_agent', 'title' => 'Hablar con Asesor', 'action' => 'escalate_agent'],
    ],
]);

// ═══════════════════════════════════════
//  KEYWORD FLOWS
// ═══════════════════════════════════════

$kws = [
    ['slug' => 'info_empresa', 'category' => 'Info Empresa', 'trigger_keywords' => ['info', 'información', 'informacion', 'quienes son', 'bgital', 'acerca'],
        'response_text' => "ℹ️ *Sobre BGITAL Telecomunicaciones*\n\nSomos una empresa mexicana de *Internet de Fibra Óptica* simétrica.\n\n🌐 Sitio web: bgital.mx\n📞 Atención por WhatsApp\n📍 Cobertura en constante crecimiento\n\n*Servicios:*\n• Planes Hogar desde 100 Mbps\n• Planes Negocio desde 100 Mbps\n• Planes Pyme desde 300 Mbps\n• Enlaces Dedicados desde 50 Mbps"],
    ['slug' => 'horarios', 'category' => 'Horarios', 'trigger_keywords' => ['horario', 'hora', 'horarios', 'cuando atienden', 'abierto', 'disponible'],
        'response_text' => "🕐 *Horario de Atención BGITAL*\n\n📅 Lunes a Viernes: 9:20 AM — 6:00 PM\n📅 Sábados: Consultar disponibilidad\n📅 Domingos: Cerrado\n\nFuera de horario el bot seguirá atendiéndote.\nPara urgencias escribe *urgente*."],
    ['slug' => 'metodos_pago', 'category' => 'Métodos de Pago', 'trigger_keywords' => ['pago', 'pagar', 'transferencia', 'deposito', 'cuenta bancaria', 'oxxo', 'factura'],
        'response_text' => "💳 *Métodos de Pago BGITAL*\n\n• Transferencia bancaria\n• Depósito en tienda\n• Pago en línea\n\nPara tu referencia de pago escribe *Soy Cliente* y validamos tu cuenta."],
    ['slug' => 'redes_sociales', 'category' => 'Redes Sociales', 'trigger_keywords' => ['facebook', 'instagram', 'redes', 'redes sociales', 'fb', 'ig'],
        'response_text' => "📱 *Síguenos en Redes Sociales*\n\n✅ Facebook: /bgital\n✅ Instagram: @bgital\n\nMantente al día con promociones y novedades."],
    ['slug' => 'instalacion', 'category' => 'Instalación', 'trigger_keywords' => ['instalacion', 'instalación', 'instalar', 'cuando instalan', 'cuanto tardan'],
        'response_text' => "🔧 *Instalación BGITAL*\n\n⏱️ Tiempo: 1-3 días hábiles\n✅ Sin costo en la mayoría de planes\n📡 Equipo ONT + Router WiFi incluido\n🔌 Cableado de fibra óptica\n\nPara contratar escribe *Contratar*."],
    ['slug' => 'sitio_web', 'category' => 'Sitio Web', 'trigger_keywords' => ['sitio', 'web', 'pagina', 'página', 'link', 'url'],
        'response_text' => "🌐 Sitio web oficial:\n\n👉 https://bgital.mx\n\nEncuentra información sobre servicios, cobertura y promociones."],
    ['slug' => 'wifi_password', 'category' => 'Contraseña WiFi', 'trigger_keywords' => ['contraseña wifi', 'password wifi', 'cambiar wifi', 'clave wifi'],
        'response_text' => "🔑 *Cambio de Contraseña WiFi*\n\n1️⃣ Conéctate a tu red WiFi\n2️⃣ Abre navegador: 192.168.1.1\n3️⃣ Usuario: admin / Contraseña: admin\n4️⃣ Busca Wireless/WiFi\n5️⃣ Cambia nombre y contraseña\n\n📌 Mínimo 8 caracteres, sin acentos."],
    ['slug' => 'despedida', 'category' => 'Despedida', 'trigger_keywords' => ['gracias', 'adios', 'adiós', 'bye', 'chao', 'hasta luego', 'listo'],
        'response_text' => "😊 ¡Gracias por comunicarte con *BGITAL*!\n\nEscribe *Menú* cuando necesites algo.\n¡Excelente día! 👋"],
];

foreach ($kws as $i => $kf) {
    BotFlow::create([
        'slug' => $kf['slug'], 'category' => $kf['category'], 'flow_type' => 'keyword',
        'trigger_keywords' => $kf['trigger_keywords'], 'response_text' => $kf['response_text'],
        'response_type' => 'text', 'is_active' => true, 'is_system_flow' => true,
        'sort_order' => $i + 1, 'flow_priority' => 10,
    ]);
}

echo "Seeded: " . BotFlow::count() . " flows, " . BotFlowStep::count() . " steps\n";
