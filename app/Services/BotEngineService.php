<?php

namespace App\Services;

use App\Models\BotFlow;
use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\Setting;
use App\Models\CoverageArea;
use App\Models\CustomerService;
use App\Models\Plan;
use App\Models\SalesLead;
use Illuminate\Support\Str;

class BotEngineService
{
    // ── Synonym dictionary for fuzzy matching ──
    private array $dictionary = [
        'planes' => ['planes', 'plan', 'plnes', 'plnaes', 'palnes', 'planess'],
        'contratar' => ['contratar', 'contrtar', 'contartar', 'quiero internet', 'quiero contratar', 'quiero servicio'],
        'precio' => ['precio', 'precios', 'presio', 'costo', 'costos', 'cuanto cuesta', 'cuanto vale'],
        'soporte' => ['soporte', 'sopote', 'soprote', 'soprte', 'ayuda', 'ayuad'],
        'internet' => ['internet', 'internett', 'intenet', 'inetrnet', 'intetnet', 'red', 'wifi'],
        'lento' => ['lento', 'lneto', 'lentoo', 'despacio', 'desapcio'],
        'falla' => ['falla', 'fala', 'fya', 'no funciona', 'nose funciona', 'no jala', 'no sirve'],
        'mantenimiento' => ['mantenimiento', 'mantenimineto', 'entendimiento', 'mantnimiento', 'mantenmiento'],
        'cobertura' => ['cobertura', 'cobertuda', 'covertuda', 'cobwertura', 'covertura'],
        'asesor' => ['asesor', 'asesr', 'acesor', 'ascesor', 'agente', 'persona', 'humano'],
        'empresa' => ['empresa', 'enpresa', 'enmpresa', 'negocio', 'negoico'],
        'residencial' => ['residencial', 'recidencial', 'residnecial', 'casa', 'hogar', 'hogra'],
        'instalacion' => ['instalacion', 'instalción', 'instalcion', 'instalar', 'intsalar'],
        'factura' => ['factura', 'facturar', 'factuara', 'recibo', 'reciebo'],
        'pago' => ['pago', 'pagoo', 'paog', 'transferencia', 'deposito', 'depósito'],
        'dedicado' => ['dedicado', 'deidcado', 'dedicaod', 'dedicdao'],
        'informacion' => ['informacion', 'información', 'info', 'infomacion', 'infromacion', 'datos'],
        'horario' => ['horario', 'horarios', 'hora', 'horas', 'horraio', 'ohorario'],
        'urgente' => ['urgente', 'urjente', 'urgenete', 'emergencia', 'emerngecia', 'critico', 'crítico'],
        'cliente' => ['cliente', 'clietne', 'cleinte', 'clinete'],
        'menu' => ['menu', 'menú', 'meu', 'meun', 'inicio', 'inciio'],
    ];

    public function handleSequence(Conversation $conversation, string $text): array
    {
        $n = $this->normalize($text);
        $state = $conversation->bot_state;
        $data = $conversation->bot_state_data ?? [];

        // ── Session timeout: 30 min inactivity → reset ──
        if ($state && $conversation->updated_at && $conversation->updated_at->diffInMinutes(now()) > 30) {
            $this->resetState($conversation);
            $state = null;
        }

        // ── Global: Outage ──
        if (Setting::getValue('is_outage_active') === 'true') {
            $faultKw = ['falla', 'lento', 'no funciona', 'sin internet', 'problema', 'señal', 'intermitente'];
            foreach ($faultKw as $kw) {
                if (Str::contains($n, $kw)) {
                    $msg = Setting::getValue('outage_message', 'Hay una falla general. Nuestro equipo ya trabaja en ello.');
                    return $this->text("⚠️ *AVISO DE RED*\n\n{$msg}\n\nTe avisaremos cuando se restablezca.");
                }
            }
        }

        // ── Global: Emergency keywords ──
        if ($this->intentMatch($n, 'urgente')) {
            $name = $data['customer_name'] ?? $conversation->contact->name ?? $conversation->contact->phone;
            $account = $data['account_number'] ?? 'N/A';
            $this->resetState($conversation);
            $conversation->status = 'waiting_agent';
            $conversation->save();
            TelegramService::sendMessage("🚨 *PRIORIDAD MÁXIMA*\n\n👤 {$name}\n🔢 Cuenta: {$account}\n📱 {$conversation->contact->phone}\n💬 _{$text}_\n\n⚡ _Escalar de inmediato._");
            return $this->text("🚨 He detectado que tu situación es *urgente*.\n\nUn asesor se comunicará contigo *de inmediato*.\n\n" . $this->workHours());
        }

        // ── Global: "menu" resets ──
        if ($this->intentMatch($n, 'menu') && $state !== null) {
            $this->resetState($conversation);
            $state = null;
        }

        // ── Conversation closed: don't respond ──
        if ($state === 'conversation_closed') {
            $this->resetState($conversation);
            $state = null;
        }

        // ═══════════════════════════════════════
        //  1. WELCOME
        // ═══════════════════════════════════════
        if (!$state) {
            $this->setState($conversation, 'awaiting_identification');
            $co = Setting::getValue('empresa_nombre', 'BGITAL');
            return $this->buttons(
                "🌐 ¡Bienvenido a *{$co}* Telecomunicaciones!\n¿Cómo podemos ayudarte hoy?",
            [['id' => 'opt_client', 'title' => 'Soy Cliente'], ['id' => 'opt_prospect', 'title' => 'Posible Cliente']]
            );
        }

        // ═══════════════════════════════════════
        //  2. IDENTIFICATION
        // ═══════════════════════════════════════
        if ($state === 'awaiting_identification') {
            if ($n === 'opt_client' || $n === '1' || $this->intentMatch($n, 'cliente') || Str::contains($n, 'soy cliente')) {
                $this->setState($conversation, 'awaiting_client_name');
                return $this->text("🔐 Para brindarte atención personalizada necesitamos validar tu identidad.\n\n✏️ Por favor escribe el *nombre completo del titular* del servicio:");
            }
            if ($n === 'opt_prospect' || $n === '2' || $this->intentMatch($n, 'contratar') || $this->intentMatch($n, 'planes') || $this->intentMatch($n, 'informacion') || Str::contains($n, 'posible')) {
                $this->setState($conversation, 'awaiting_coverage_input');
                return $this->text("📍 Para verificar la cobertura en tu zona, por favor envíanos tu *Código Postal* (5 dígitos):");
            }
        }

        // ═══════════════════════════════════════
        //  3. CLIENT FLOW
        // ═══════════════════════════════════════

        // ── 3.1 Client name ──
        if ($state === 'awaiting_client_name') {
            $data['client_name_input'] = trim($text);
            $data['validation_attempts'] = $data['validation_attempts'] ?? 0;
            $this->setState($conversation, 'awaiting_client_account', $data);
            return $this->text("Gracias, *{$data['client_name_input']}*.\n\nAhora ingresa tu *Número de Cuenta*:\n📌 _Lo encuentras en tu recibo o contrato (ej. BG-100)_");
        }

        // ── 3.2 Client account validation ──
        if ($state === 'awaiting_client_account') {
            $accountInput = strtoupper(trim($text));
            $nameInput = $data['client_name_input'] ?? '';
            $attempts = ($data['validation_attempts'] ?? 0) + 1;

            $service = CustomerService::with('customer')
                ->where('account_number', $accountInput)
                ->first();

            $nameMatch = false;
            if ($service && $service->customer) {
                $dbName = $this->normalize($service->customer->name);
                $inputName = $this->normalize($nameInput);
                $similarity = 0;
                similar_text($dbName, $inputName, $similarity);
                $nameMatch = $similarity >= 60; // 60% is enough for names
            }

            if ($service && $nameMatch) {
                $customer = $service->customer;
                $stateData = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'service_id' => $service->id,
                    'account_number' => $service->account_number,
                    'plan_name' => $service->plan_name,
                    'service_label' => $service->label,
                    'is_active' => $service->is_active,
                ];

                if (!$service->is_active) {
                    $this->setState($conversation, 'awaiting_suspended_action', $stateData);
                    return $this->buttons(
                        "⚠️ *{$customer->name}*, tu servicio *{$service->account_number}* se encuentra *suspendido*.\n\n¿Qué deseas hacer?",
                    [['id' => 'opt_pay', 'title' => 'Pagar / Reactivar'], ['id' => 'opt_agent', 'title' => 'Hablar con Asesor']]
                    );
                }

                $this->setState($conversation, 'awaiting_client_menu', $stateData);
                $planInfo = $service->plan_name ? " | Plan: *{$service->plan_name}*" : "";
                $label = $service->label ? " ({$service->label})" : "";
                return $this->listMenu(
                    "👋 ¡Hola, *{$customer->name}*!\n\nServicio: *{$service->account_number}*{$label}{$planInfo}\nEstado: ✅ Activo\n\n¿En qué te podemos ayudar?",
                [['title' => 'Opciones', 'rows' => [
                            ['id' => 'cl_fault', 'title' => 'Reportar Falla', 'description' => 'Tengo problemas con mi internet'],
                            ['id' => 'cl_info', 'title' => 'Info de mi Servicio', 'description' => 'Ver datos de mi contrato'],
                            ['id' => 'cl_agent', 'title' => 'Hablar con Asesor', 'description' => 'Atención personalizada'],
                        ]]],
                    'Ver Opciones'
                );
            }

            // Failed
            $data['validation_attempts'] = $attempts;
            if ($attempts >= 3) {
                $this->setState($conversation, 'client_validation_failed', $data);
                TelegramService::sendMessage("🔒 *Validación Fallida (3 intentos)*\n\n👤 Nombre ingresado: {$nameInput}\n🔢 Cuenta ingresada: {$accountInput}\n📱 {$conversation->contact->phone}");
                return $this->buttons(
                    "❌ Has alcanzado el *máximo de intentos* de validación.\n\n¿Qué te gustaría hacer?",
                [['id' => 'opt_retry', 'title' => 'Intentar de Nuevo'], ['id' => 'opt_agent', 'title' => 'Hablar con Asesor']]
                );
            }
            $remaining = 3 - $attempts;
            $this->setState($conversation, 'awaiting_client_name', $data);
            return $this->text("❌ No encontré esa combinación de nombre y cuenta.\n\nTe quedan *{$remaining}* intentos.\n\nIngresa nuevamente el *nombre completo del titular*:");
        }

        // ── 3.3 Validation failed options ──
        if ($state === 'client_validation_failed') {
            if ($n === 'opt_retry') {
                $data['validation_attempts'] = 0;
                $this->setState($conversation, 'awaiting_client_name', $data);
                return $this->text("🔄 Sin problema. Ingresa nuevamente el *nombre completo del titular*:");
            }
            return $this->escalateToAgent($conversation, 'Cliente falló validación 3 veces. Necesita ayuda para identificar su cuenta.');
        }

        // ── Suspended service ──
        if ($state === 'awaiting_suspended_action') {
            if ($n === 'opt_pay' || $this->intentMatch($n, 'pago')) {
                $this->setState($conversation, 'conversation_closed');
                TelegramService::sendMessage("💳 *Servicio Suspendido — Cliente quiere pagar*\n\n👤 {$data['customer_name']}\n🔢 Cuenta: {$data['account_number']}\n📱 {$conversation->contact->phone}\n\n_Requiere reactivación._");
                return $this->buttons("💳 He notificado al equipo de *cobranza*.\n\nUn asesor te contactará para coordinar el pago y reactivación.\n\n" . $this->workHours(), [['id' => 'menu', 'title' => 'Menú Principal']]);
            }
            return $this->escalateToAgent($conversation, "Servicio suspendido. Cuenta: {$data['account_number']}");
        }

        // ── 3.4 Client menu ──
        if ($state === 'awaiting_client_menu') {
            if ($n === 'cl_fault' || $n === '1' || $this->intentMatch($n, 'falla') || $this->intentMatch($n, 'soporte') || $this->intentMatch($n, 'lento')) {
                $this->setState($conversation, 'awaiting_fault_type', $data);
                return $this->listMenu(
                    "🔧 ¿Cuál es el problema que presentas?",
                [['title' => 'Tipo de Problema', 'rows' => [
                            ['id' => 'f_no_net', 'title' => 'Sin Internet', 'description' => 'No tengo conexión a internet'],
                            ['id' => 'f_slow', 'title' => 'Internet Lento', 'description' => 'Velocidad menor a la contratada'],
                            ['id' => 'f_intermittent', 'title' => 'Intermitencia', 'description' => 'Se cae y regresa constantemente'],
                            ['id' => 'f_router', 'title' => 'Problema con Router', 'description' => 'El equipo no enciende o falla'],
                            ['id' => 'f_other', 'title' => 'Otro Problema', 'description' => 'Mi problema no está en la lista'],
                        ]]],
                    'Ver Problemas'
                );
            }
            if ($n === 'cl_info' || $n === '2' || $this->intentMatch($n, 'informacion')) {
                $plan = $data['plan_name'] ?? 'No registrado';
                $label = $data['service_label'] ?? 'Principal';
                return $this->buttons(
                    "📋 *Información de tu Servicio*\n\n👤 Titular: *{$data['customer_name']}*\n🔢 Cuenta: *{$data['account_number']}*\n🏷️ Etiqueta: {$label}\n📦 Plan: *{$plan}*\n✅ Estado: Activo\n\n¿Necesitas algo más?",
                [['id' => 'cl_agent', 'title' => 'Hablar con Asesor'], ['id' => 'menu', 'title' => 'Menú Principal']]
                );
            }
            if ($n === 'cl_agent' || $n === '3' || $this->intentMatch($n, 'asesor')) {
                return $this->escalateToAgent($conversation, "Cliente validado solicita asesor. Cuenta: {$data['account_number']}");
            }
        }

        // ═══════════════════════════════════════
        //  4. SUPPORT FLOW
        // ═══════════════════════════════════════

        if ($state === 'awaiting_fault_type') {
            $faultMap = [
                'f_no_net' => 'Sin Internet', 'f_slow' => 'Internet Lento',
                'f_intermittent' => 'Intermitencia', 'f_router' => 'Problema con Router',
                'f_other' => 'Otro Problema',
            ];
            $faultKey = $faultMap[$n] ?? null;
            if (!$faultKey) {
                // Try fuzzy matching fault keywords
                if ($this->intentMatch($n, 'internet') && ($this->intentMatch($n, 'lento') || Str::contains($n, 'lent')))
                    $faultKey = 'Internet Lento';
                elseif (Str::contains($n, 'no tengo') || Str::contains($n, 'sin internet'))
                    $faultKey = 'Sin Internet';
                elseif (Str::contains($n, 'intermit') || Str::contains($n, 'se cae'))
                    $faultKey = 'Intermitencia';
                elseif (Str::contains($n, 'router') || Str::contains($n, 'modem') || Str::contains($n, 'no enciende'))
                    $faultKey = 'Problema con Router';
                else
                    $faultKey = 'Otro Problema';
            }

            $data['fault_label'] = $faultKey;
            $this->setState($conversation, 'awaiting_solution_confirmation', $data);
            $solutions = $this->getSolutions($faultKey);
            return $this->buttons($solutions, [['id' => 'ts_solved', 'title' => 'Ya Funciona ✅'], ['id' => 'ts_not_solved', 'title' => 'Sigue Fallando ❌']]);
        }

        // ── 4.1 Solution confirmation ──
        if ($state === 'awaiting_solution_confirmation') {
            if ($n === 'ts_solved' || $n === '1' || Str::contains($n, 'funciona') || Str::contains($n, 'soluciono') || Str::contains($n, 'resolvio')) {
                $this->setState($conversation, 'conversation_closed');
                return $this->text("✅ ¡Excelente! Me alegra que se haya solucionado.\n\nGracias por comunicarte con *BGITAL*. Escribe *Menú* si necesitas algo más.\n\n¡Excelente día! 👋");
            }
            // Not solved → escalate to agent (recommended approach)
            $this->escalateToAgent($conversation, "Problema no resuelto: {$data['fault_label']}. Cuenta: {$data['account_number']}. El cliente ya intentó las soluciones guiadas.");
            TelegramService::sendMessage("🚨 *Soporte No Resuelto*\n\n👤 {$data['customer_name']}\n🔢 Cuenta: {$data['account_number']}\n⚠️ Falla: {$data['fault_label']}\n📱 {$conversation->contact->phone}\n\n_Soluciones guiadas no funcionaron. Se requiere técnico._");
            return $this->text("😔 Lamento que el problema persista.\n\nHe notificado a un *asesor de soporte técnico* con tu caso. Te contactará para evaluar tu problema y, si es necesario, agendar una visita técnica.\n\n" . $this->workHours());
        }

        // ═══════════════════════════════════════
        //  5. PROSPECT FLOW
        // ═══════════════════════════════════════

        // ── 5.1 Coverage check ──
        if ($state === 'awaiting_coverage_input') {
            $zip = $this->extractZip($text);
            if (!$zip) {
                return $this->text("📌 Por favor ingresa un *Código Postal válido* de 5 dígitos.\n\n_Ejemplo: 50000_");
            }

            $results = CoverageArea::where('is_active', true)->where('zip_code', $zip)->get();

            if ($results->count() > 0) {
                $zones = $results->pluck('neighborhood')->unique()->take(5)->implode(', ');
                $data['zip_code'] = $zip;
                $data['coverage_zones'] = $zones;
                $this->setState($conversation, 'awaiting_prospect_menu', $data);
                return $this->buttons(
                    "✅ *¡Tenemos cobertura en tu zona!* 🚀\n\n📍 CP: {$zip}\n🏘️ Zonas: {$zones}\n\n¿Qué te gustaría hacer?",
                [['id' => 'opt_plans', 'title' => 'Ver Planes 📋'], ['id' => 'opt_web', 'title' => 'Sitio Web 🌐'], ['id' => 'opt_agent', 'title' => 'Hablar con Asesor']]
                );
            }

            // No coverage
            $data['zip_code'] = $zip;
            $this->setState($conversation, 'awaiting_no_coverage_action', $data);
            TelegramService::notifyNoCoverageLead($conversation->contact->phone, $zip, $text);
            return $this->buttons(
                "📍 *Zona en Expansión*\n\nActualmente no tenemos red activa en CP *{$zip}*.\n\nHe notificado a nuestro equipo para evaluar la viabilidad.\n\n¿Qué deseas hacer?",
            [['id' => 'opt_agent', 'title' => 'Hablar con Asesor'], ['id' => 'opt_end', 'title' => 'Finalizar Chat']]
            );
        }

        // ── No coverage actions ──
        if ($state === 'awaiting_no_coverage_action') {
            if ($n === 'opt_end' || Str::contains($n, 'finalizar')) {
                $this->setState($conversation, 'conversation_closed');
                return $this->text("😊 Gracias por tu interés en *BGITAL*.\n\nCuando tengamos cobertura en tu zona te lo haremos saber. ¡Hasta pronto! 👋");
            }
            return $this->escalateToAgent($conversation, "Prospecto sin cobertura (CP: {$data['zip_code']}) solicita evaluación de infraestructura.");
        }

        // ── 5.2 Prospect menu ──
        if ($state === 'awaiting_prospect_menu') {
            if ($n === 'opt_plans' || $n === '1' || $this->intentMatch($n, 'planes')) {
                $this->setState($conversation, 'awaiting_client_type', $data);
                return $this->buttons(
                    "🏠🏢 ¿Qué tipo de servicio te interesa?",
                [['id' => 'type_home', 'title' => 'Internet para Casa'], ['id' => 'type_business', 'title' => 'Internet Empresa']]
                );
            }
            if ($n === 'opt_web' || $n === '2' || $this->intentMatch($n, 'internet') && Str::contains($n, 'web')) {
                $web = Setting::getValue('empresa_web', 'https://bgital.mx');
                return $this->buttons("🌐 Visita nuestro sitio:\n\n👉 {$web}\n\n¿Algo más?", [['id' => 'menu', 'title' => 'Menú Principal']]);
            }
            if ($n === 'opt_agent' || $n === '3' || $this->intentMatch($n, 'asesor')) {
                return $this->escalateToAgent($conversation, "Prospecto con cobertura (CP: {$data['zip_code']}) solicita asesor de ventas.");
            }
        }

        // ═══════════════════════════════════════
        //  6. CLIENT TYPE (Casa / Empresa)
        // ═══════════════════════════════════════

        if ($state === 'awaiting_client_type') {
            if ($n === 'type_home' || $n === '1' || $this->intentMatch($n, 'residencial')) {
                $data['client_type'] = 'residencial';
                return $this->showPlanCategory($conversation, 'HOGAR', $data);
            }
            if ($n === 'type_business' || $n === '2' || $this->intentMatch($n, 'empresa')) {
                $data['client_type'] = 'empresa';
                $this->setState($conversation, 'awaiting_business_category', $data);
                return $this->buttons(
                    "🏢 *Planes Empresariales*\n\n¿Qué tipo de negocio tienes?",
                [['id' => 'biz_small', 'title' => 'Negocio Pequeño 🏪'], ['id' => 'biz_medium', 'title' => 'Empresa Mediana 🏢'], ['id' => 'biz_large', 'title' => 'Empresa Grande 💎']]
                );
            }
        }

        // ── Business category ──
        if ($state === 'awaiting_business_category') {
            if ($n === 'biz_small' || $n === '1') {
                return $this->showPlanCategory($conversation, 'NEGOCIO', $data);
            }
            if ($n === 'biz_medium' || $n === '2') {
                return $this->showPlanCategory($conversation, 'PYME', $data);
            }
            if ($n === 'biz_large' || $n === '3' || $this->intentMatch($n, 'dedicado')) {
                return $this->showPlanCategory($conversation, 'DEDICADO', $data);
            }
        }

        // ═══════════════════════════════════════
        //  7. PLAN SELECTION
        // ═══════════════════════════════════════

        if ($state === 'awaiting_plan_selection') {
            $planId = str_replace('plan_', '', $n);
            $plan = Plan::find($planId);

            if (!$plan) {
                // Try fuzzy match by name
                $plan = Plan::where('is_active', true)
                    ->where('category', $data['selected_category'] ?? null)
                    ->get()
                    ->first(function ($p) use ($n) {
                    $pName = $this->normalize($p->name);
                    $sim = 0;
                    similar_text($pName, $n, $sim);
                    return $sim >= 60;
                });
            }

            $planName = $plan ? $plan->name : $text;
            $priceLabel = ($plan && $plan->price > 0) ? '$' . number_format($plan->price) . '/mes' : 'A Negociar';
            $speed = $plan->speed ?? '';
            $description = $plan->description ?? '';

            // If Dedicated → special flow
            if ($plan && strtoupper($plan->category) === 'DEDICADO') {
                $data['selected_plan'] = $planName;
                $data['selected_speed'] = $speed;
                $this->setState($conversation, 'awaiting_dedicated_info', $data);
                return $this->text("💎 *Internet Dedicado — {$speed}*\n\nEste servicio es personalizado y diseñado para las necesidades de tu empresa.\n\nPara darte una cotización, necesitamos:\n\n✏️ *Nombre de tu empresa y tu nombre de contacto*\n_(Ejemplo: Empresa X, Juan Pérez)_");
            }

            // Regular plan → register lead + notify
            $this->registerLead($conversation, $data, $planName, $priceLabel);

            $detailText = $description ? "\n📝 {$description}" : "";
            $this->setState($conversation, 'conversation_closed');
            return $this->buttons(
                "✅ *¡Excelente decisión!*\n\n📦 Plan: *{$planName}*\n⚡ Velocidad: {$speed}\n💰 Precio: {$priceLabel}{$detailText}\n\nHe registrado tu interés. Un asesor de ventas se comunicará contigo para coordinar la contratación e instalación.\n\n" . $this->workHours(),
            [['id' => 'menu', 'title' => 'Menú Principal']]
            );
        }

        // ── Dedicated internet: collect company info ──
        if ($state === 'awaiting_dedicated_info') {
            $data['company_info'] = trim($text);
            $this->setState($conversation, 'conversation_closed');

            $this->registerLead($conversation, $data, $data['selected_plan'] ?? 'Dedicado', 'A Negociar', 'empresa', $data['company_info']);

            TelegramService::sendMessage("💎 *Solicitud Internet Dedicado*\n\n🏢 Empresa/Contacto: {$data['company_info']}\n📡 Velocidad: {$data['selected_speed']}\n📍 CP: {$data['zip_code']}\n📱 {$conversation->contact->phone}\n\n_Requiere cotización personalizada._");

            return $this->buttons(
                "✅ *Solicitud recibida.*\n\n🏢 {$data['company_info']}\n📡 Interés: Internet Dedicado {$data['selected_speed']}\n\nUn asesor especializado te contactará para una cotización personalizada.\n\n" . $this->workHours(),
            [['id' => 'menu', 'title' => 'Menú Principal']]
            );
        }

        // ═══════════════════════════════════════
        //  FALLBACK — BotFlows keyword match
        // ═══════════════════════════════════════
        $flow = $this->matchFlow($n);
        if ($flow)
            return $this->executeFlow($conversation, $flow);

        // ── Final fallback with intent detection ──
        if ($this->intentMatch($n, 'planes') || $this->intentMatch($n, 'contratar') || $this->intentMatch($n, 'precio')) {
            $this->setState($conversation, 'awaiting_coverage_input');
            return $this->text("📋 Para mostrarte nuestros planes, primero necesito verificar cobertura.\n\n✏️ Ingresa tu *Código Postal* (5 dígitos):");
        }
        if ($this->intentMatch($n, 'falla') || $this->intentMatch($n, 'soporte') || $this->intentMatch($n, 'lento')) {
            $this->setState($conversation, 'awaiting_client_name');
            return $this->text("🔧 Para darte soporte técnico necesitamos verificar tu cuenta.\n\n✏️ Ingresa el *nombre completo del titular* del servicio:");
        }
        if ($this->intentMatch($n, 'asesor')) {
            return $this->escalateToAgent($conversation, 'Usuario solicita asesor desde texto libre.');
        }

        return $this->buttons(
            "🤔 No entendí tu mensaje.\n\nPuedes escribir lo que necesitas o seleccionar una opción:",
        [['id' => 'menu', 'title' => 'Menú Principal'], ['id' => 'opt_agent', 'title' => 'Hablar con Asesor']]
        );
    }

    // ═══════════════════════════════════════
    //  HELPER: Show plans by category
    // ═══════════════════════════════════════

    private function showPlanCategory(Conversation $conversation, string $category, array $data): array
    {
        $plans = Plan::where('category', $category)->where('is_active', true)->orderBy('price')->get();
        $data['selected_category'] = $category;
        $this->setState($conversation, 'awaiting_plan_selection', $data);

        if ($plans->isEmpty()) {
            return $this->text("No hay planes activos en *{$category}*. Escribe *Menú* para volver.");
        }

        $rows = [];
        foreach ($plans as $p) {
            $priceLabel = $p->price > 0 ? ('$' . number_format($p->price) . '/mes') : 'A Negociar';
            $rows[] = [
                'id' => "plan_{$p->id}",
                'title' => substr($p->name, 0, 24),
                'description' => substr("{$p->speed} — {$priceLabel}", 0, 72),
            ];
        }

        $catLabels = ['HOGAR' => '🏠 Hogar', 'NEGOCIO' => '🏢 Negocio', 'PYME' => '🏬 Pyme', 'DEDICADO' => '💎 Dedicado'];
        $label = $catLabels[$category] ?? $category;

        return $this->listMenu(
            "📋 *Planes BGITAL — {$label}*\n(IVA incluido, velocidad simétrica)\n\nSelecciona el plan que te interesa:",
        [['title' => "Planes {$category}", 'rows' => $rows]],
            'Ver Planes'
        );
    }

    // ═══════════════════════════════════════
    //  HELPER: Register lead
    // ═══════════════════════════════════════

    private function registerLead(Conversation $conversation, array $data, string $planName, string $price, string $clientType = null, string $companyName = null): void
    {
        $type = $clientType ?? ($data['client_type'] ?? 'residencial');
        $zip = $data['zip_code'] ?? null;
        $phone = $conversation->contact->phone;
        $contactName = $conversation->contact->name ?? $phone;

        SalesLead::create([
            'contact_id' => $conversation->contact->id,
            'conversation_id' => $conversation->id,
            'plan_interest' => $planName,
            'client_type' => $type,
            'zip_code' => $zip,
            'company_name' => $companyName,
            'phone' => $phone,
            'source' => 'whatsapp',
            'status' => 'pending',
        ]);

        $typeLabel = $type === 'empresa' ? '🏢 Empresa' : '🏠 Residencial';

        TelegramService::sendMessage("📡 *Nuevo Lead BGITAL*\n\n👤 Nombre: {$contactName}\n{$typeLabel}\n📦 Plan: {$planName} ({$price})\n📍 CP: {$zip}\n📱 Tel: {$phone}" . ($companyName ? "\n🏢 Empresa: {$companyName}" : ""));
    }

    // ═══════════════════════════════════════
    //  HELPER: Escalate to agent
    // ═══════════════════════════════════════

    private function escalateToAgent(Conversation $conversation, string $reason): array
    {
        $data = $conversation->bot_state_data ?? [];
        $name = $data['customer_name'] ?? $conversation->contact->name ?? $conversation->contact->phone;
        $account = $data['account_number'] ?? 'N/A';

        $this->resetState($conversation);
        $conversation->status = 'waiting_agent';
        $conversation->save();

        TelegramService::sendMessage("👨‍💻 *Escalación a Asesor*\n\n👤 {$name}\n🔢 Cuenta: {$account}\n📱 {$conversation->contact->phone}\n📝 {$reason}");

        return $this->text("👨‍💻 He desactivado el bot para esta conversación.\n\nUn asesor te atenderá personalmente.\n\n" . $this->workHours());
    }

    // ═══════════════════════════════════════
    //  HELPER: Solutions
    // ═══════════════════════════════════════

    private function getSolutions(string $faultLabel): string
    {
        $map = [
            'Sin Internet' => "🔧 *Sin Internet — Guía de Solución*\n\n*Paso 1:* Verifica que el cable de fibra óptica (blanco y delgado) esté bien conectado al ONT/Modem.\n\n*Paso 2:* Desconecta la energía del modem durante *60 segundos* y vuélvelo a conectar.\n\n*Paso 3:* Espera 3 minutos a que reinicie completamente.\n\n*Paso 4:* Revisa que las luces *Power* y *PON/LOS* estén fijas (no parpadeando en rojo).\n\n¿Se solucionó tu problema?",
            'Internet Lento' => "🔧 *Internet Lento — Guía de Solución*\n\n*Paso 1:* Desconecta repetidores o extensores WiFi.\n\n*Paso 2:* Conecta un dispositivo *por cable Ethernet* directamente al modem.\n\n*Paso 3:* Haz un test de velocidad en *fast.com*\n\n*Paso 4:* Si por cable es correcto, el problema es WiFi. Acércate al modem.\n\n¿Mejoró tu velocidad?",
            'Intermitencia' => "🔧 *Señal Intermitente — Guía de Solución*\n\n*Paso 1:* Verifica que el cable de fibra no esté doblado ni prensado.\n\n*Paso 2:* Reinicia el modem (desconecta 60 seg).\n\n*Paso 3:* Revisa si el problema ocurre a ciertas horas.\n\n*Paso 4:* Conecta por cable Ethernet para descartar WiFi.\n\n¿Se estabilizó tu conexión?",
            'Problema con Router' => "🔧 *Problema con Router — Guía de Solución*\n\n*Paso 1:* Verifica que el cable de energía esté bien conectado.\n\n*Paso 2:* Revisa que la luz *Power* esté encendida.\n\n*Paso 3:* Si no enciende, prueba con otra toma de corriente.\n\n*Paso 4:* Si las luces parpadean anormalmente, necesitas revisión técnica.\n\n¿Se resolvió?",
        ];
        return $map[$faultLabel] ?? "🔧 *Soporte Técnico*\n\n*Paso 1:* Reinicia tu modem desconectándolo 60 segundos.\n\n*Paso 2:* Verifica todas las conexiones físicas.\n\n*Paso 3:* Si persiste, un técnico debe revisar.\n\n¿Se solucionó?";
    }

    // ═══════════════════════════════════════
    //  FUZZY MATCHING
    // ═══════════════════════════════════════

    /**
     * Check if normalized text matches an intent using:
     * 1. Direct substring match
     * 2. Dictionary + combined score (similar_text * 0.7 + levenshtein * 0.3)
     * Threshold: 75% combined score
     */
    private function intentMatch(string $normalizedText, string $intent): bool
    {
        // 1. Direct match
        if (Str::contains($normalizedText, $intent))
            return true;

        // 2. Dictionary synonyms — direct substring
        $synonyms = $this->dictionary[$intent] ?? [$intent];
        foreach ($synonyms as $syn) {
            if (Str::contains($normalizedText, $syn))
                return true;
        }

        // 3. Fuzzy: check each word in the text against the intent and its synonyms
        $words = explode(' ', $normalizedText);
        $targets = array_merge([$intent], $synonyms);

        foreach ($words as $word) {
            if (strlen($word) < 3)
                continue;
            foreach ($targets as $target) {
                if (strlen($target) < 3)
                    continue;
                $score = $this->fuzzyScore($word, $target);
                if ($score >= 75)
                    return true;
            }
        }

        return false;
    }

    /**
     * Combined score: similar_text% * 0.7 + normalized_levenshtein% * 0.3
     */
    private function fuzzyScore(string $a, string $b): float
    {
        $simPercent = 0;
        similar_text($a, $b, $simPercent);

        $maxLen = max(strlen($a), strlen($b));
        $levDist = levenshtein($a, $b);
        $levPercent = $maxLen > 0 ? ((1 - ($levDist / $maxLen)) * 100) : 0;

        return ($simPercent * 0.7) + ($levPercent * 0.3);
    }

    // ═══════════════════════════════════════
    //  BOT FLOW FALLBACK
    // ═══════════════════════════════════════

    private function matchFlow(string $n): ?BotFlow
    {
        $flows = BotFlow::where('is_active', true)->orderBy('sort_order')->get();
        foreach ($flows as $flow) {
            $kw = is_string($flow->trigger_keywords) ? json_decode($flow->trigger_keywords, true) : $flow->trigger_keywords;
            if (!$kw || !is_array($kw))
                continue;
            foreach ($kw as $keyword) {
                if (Str::contains($n, mb_strtolower(trim($keyword))))
                    return $flow;
            }
        }
        return null;
    }

    private function executeFlow(Conversation $conversation, BotFlow $flow): array
    {
        if ($flow->response_type === 'handoff') {
            return $this->escalateToAgent($conversation, 'Escalación desde flujo: ' . $flow->category);
        }
        if ($flow->response_type === 'coverage_checker') {
            $this->setState($conversation, 'awaiting_coverage_input');
            return $this->text($flow->response_text . "\n\n✏️ Ingresa tu *Código Postal*:");
        }
        $response = ['type' => 'text', 'text' => $flow->response_text];
        if ($flow->response_type === 'buttons' && $flow->response_buttons) {
            $btns = is_string($flow->response_buttons) ? json_decode($flow->response_buttons, true) : $flow->response_buttons;
            if (is_array($btns))
                $response['buttons'] = array_slice($btns, 0, 3);
        }
        return $response;
    }

    // ═══════════════════════════════════════
    //  UTILITIES
    // ═══════════════════════════════════════

    private function extractZip(string $text): ?string
    {
        $cleaned = preg_replace('/[^0-9]/', '', trim($text));
        if (strlen($cleaned) >= 5)
            return substr($cleaned, 0, 5);
        preg_match('/\d{5}/', $text, $m);
        return $m[0] ?? null;
    }

    private function text(string $msg): array
    {
        return ['type' => 'text', 'text' => $msg];
    }

    private function buttons(string $msg, array $btns): array
    {
        $f = [];
        foreach (array_slice($btns, 0, 3) as $b) {
            $f[] = ['type' => 'reply', 'reply' => ['id' => $b['id'], 'title' => substr($b['title'], 0, 20)]];
        }
        return ['type' => 'text', 'text' => $msg, 'buttons' => $f];
    }

    private function listMenu(string $msg, array $sections, string $btnText = 'Ver Opciones'): array
    {
        return ['type' => 'list', 'text' => $msg, 'list_sections' => $sections, 'list_button_text' => $btnText];
    }

    private function workHours(): string
    {
        $now = now()->format('H:i');
        $start = Setting::getValue('work_hours_start', '09:20');
        $end = Setting::getValue('work_hours_end', '18:00');
        $co = Setting::getValue('empresa_nombre', 'BGITAL');
        if ($now >= $start && $now <= $end)
            return "Un asesor de *{$co}* te contactará en breve.";
        return "🕐 Horario: *Lun-Vie {$start} - {$end}*. Tu solicitud será atendida el siguiente día hábil.";
    }

    private function setState(Conversation $c, string $state, ?array $data = null): void
    {
        $c->bot_state = $state;
        if ($data !== null)
            $c->bot_state_data = $data;
        $c->save();
    }

    private function resetState(Conversation $c): void
    {
        $c->bot_state = null;
        $c->bot_state_data = null;
        $c->save();
    }

    protected function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
        return preg_replace('/[^\w\s]/', '', $t);
    }
}
