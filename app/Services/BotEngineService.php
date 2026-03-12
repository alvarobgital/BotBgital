<?php

namespace App\Services;

use App\Models\BotFlow;
use App\Models\BotFlowStep;
use App\Models\Conversation;
use App\Models\CoverageArea;
use App\Models\CustomerService;
use App\Models\SalesLead;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BotEngineService
{
    public function handleSequence(Conversation $conversation, string $text): array
    {
        $normalizedText = $this->normalize($text);

        // 1. Session expiration reset (30 min)
        if ($conversation->bot_state && $conversation->updated_at && $conversation->updated_at->diffInMinutes(now()) > 30) {
            $this->resetState($conversation);
        }

        // 2. Global Commands (e.g. Menu)
        $isGlobalRestart = false;
        if (in_array($normalizedText, ['menu', 'inicio', 'menú', 'salir', 'reiniciar', 'nueva conversacion', 'nueva', 'empezar de nuevo']) || str_contains($normalizedText, 'reiniciar')) {
            $this->resetState($conversation);
            $conversation->refresh();
            $isGlobalRestart = true;
        }

        // 3. Current active step execution
        if ($conversation->bot_state) {
            $currentStep = BotFlowStep::where('step_key', $conversation->bot_state)->first();
            if ($currentStep) {
                return $this->processStepInput($conversation, $currentStep, $text, $normalizedText);
            }
        }

        // If manually restarted, force entry to MAIN flow
        if ($isGlobalRestart) {
            $mainFlow = BotFlow::where('flow_type', 'main')->where('is_active', true)->orderByDesc('flow_priority')->orderBy('sort_order')->first();
            if ($mainFlow) {
                $entryStep = $mainFlow->steps()->where('is_entry_point', true)->first();
                if ($entryStep)
                    return $this->executeStep($conversation, $entryStep);
            }
        }

        // 4. Match Flow by Keywords
        $flow = $this->matchFlow($normalizedText);
        if ($flow) {
            if ($flow->flow_type === 'keyword') {
                return $this->text($flow->response_text ?? '');
            }

            $entryStep = $flow->steps()->where('is_entry_point', true)->first();
            if ($entryStep) {
                return $this->executeStep($conversation, $entryStep);
            }
        }

        // 5. Fallback (did not understand)
        $fallbackFlow = BotFlow::where('slug', 'fallback')->first();
        if ($fallbackFlow) {
            $entryStep = $fallbackFlow->steps()->where('is_entry_point', true)->first();
            if ($entryStep) {
                return $this->executeStep($conversation, $entryStep);
            }
        }

        return $this->text("🤔 No entendí tu mensaje.\n\n_(Puedes escribir *reiniciar* en cualquier momento para empezar una nueva conversación)_");
    }

    private function processStepInput(Conversation $conversation, BotFlowStep $step, string $rawText, string $normalizedText): array
    {
        $data = $conversation->bot_state_data ?? [];

        // Save raw input if requested by step validation rules
        if ($step->input_validation === 'name') {
            $data[$step->step_key] = trim($rawText);
        }
        elseif ($step->input_validation === 'account_number') {
            $data[$step->step_key] = strtoupper(trim($rawText));
        }
        elseif ($step->input_validation === 'zip_code') {
            $data[$step->step_key] = preg_replace('/[^0-9]/', '', $rawText);
        }
        else {
            // General text input save
            $data[$step->step_key] = trim($rawText);
        }

        $conversation->bot_state_data = $data;
        $conversation->save();

        // Check if user clicked an option button
        $options = $step->options ?? [];
        $selectedOption = null;

        // Check exact matches first
        foreach ($options as $opt) {
            if ($normalizedText === mb_strtolower(trim($opt['id'])) || $normalizedText === mb_strtolower(trim($opt['title']))) {
                $selectedOption = $opt;
                break;
            }
        }

        // Check fuzzy matches if no exact match
        if (!$selectedOption) {
            foreach ($options as $opt) {
                if ($this->fuzzyMatch($normalizedText, mb_strtolower(trim($opt['title'])))) {
                    $selectedOption = $opt;
                    break;
                }
            }
        }

        // Intercept Dynamic Plans & Categories
        if (!$selectedOption && $step->action_type === 'show_plan_categories') {
            $categories = \App\Models\Plan::where('is_active', true)->whereNotNull('category')->distinct()->pluck('category');
            foreach ($categories as $cat) {
                if ($normalizedText === 'cat_' . \Illuminate\Support\Str::slug($cat) || $this->fuzzyMatch($normalizedText, mb_strtolower($cat))) {
                    $selectedOption = [
                        'action' => 'show_plans',
                        'action_config' => ['category' => $cat]
                    ];
                    break;
                }
            }
        }

        if (!$selectedOption && $step->action_type === 'show_plans') {
            $plans = \App\Models\Plan::where('is_active', true)->get();
            foreach ($plans as $plan) {
                $planId = (string)$plan->id;
                // Match ID (with or without underscore), fuzzy name match, or exact name match
                if (
                    $normalizedText === 'plan_' . $planId || 
                    $normalizedText === 'plan' . $planId || 
                    str_contains($normalizedText, 'plan' . $planId) ||
                    $this->fuzzyMatch($normalizedText, mb_strtolower('Me interesa ' . $plan->name)) || 
                    $this->fuzzyMatch($normalizedText, mb_strtolower($plan->name))
                ) {
                    $selectedOption = [
                        'action' => 'select_plan',
                        'action_config' => ['plan_id' => $plan->id, 'plan_name' => $plan->name]
                    ];
                    break;
                }
            }
        }

        if ($selectedOption) {
            if (!empty($selectedOption['action'])) {
                $actionResult = $this->executeAction($conversation, $selectedOption['action'], $data, $selectedOption['action_config'] ?? []);

                if (isset($actionResult['_next_step'])) {
                    $nextStep = BotFlowStep::where('step_key', $actionResult['_next_step'])->first();
                    if ($nextStep)
                        return $this->executeStep($conversation, $nextStep);
                }
                if (isset($actionResult['type'])) {
                    return $actionResult;
                }
            }
            if (!empty($selectedOption['next_step'])) {
                $nextStep = BotFlowStep::where('step_key', $selectedOption['next_step'])->first();
                if ($nextStep)
                    return $this->executeStep($conversation, $nextStep);
            }
            if (!empty($selectedOption['next_flow'])) {
                $flow = BotFlow::where('slug', $selectedOption['next_flow'])->first();
                if ($flow) {
                    $entryStep = $flow->steps()->where('is_entry_point', true)->first();
                    if ($entryStep)
                        return $this->executeStep($conversation, $entryStep);
                }
            }
        }

        // No option selected, but input was provided. Run action if defined.
        if ($step->action_type) {
            $actionResult = $this->executeAction($conversation, $step->action_type, $data, $step->action_config);

            // If action wants to override the next step (like coverage routing)
            if (isset($actionResult['_next_step'])) {
                $nextStep = BotFlowStep::where('step_key', $actionResult['_next_step'])->first();
                if ($nextStep)
                    return $this->executeStep($conversation, $nextStep);
            }

            // If action returns an immediate response (like escalating to agent)
            if (isset($actionResult['type'])) {
                return $actionResult;
            }
        }

        // Proceed to next default step
        if ($step->next_step_default) {
            $nextStep = BotFlowStep::where('step_key', $step->next_step_default)->first();
            if ($nextStep)
                return $this->executeStep($conversation, $nextStep);
        }

        // End of the line
        return $this->executeAction($conversation, 'close_conversation', $data);
    }

    private function executeStep(Conversation $conversation, BotFlowStep $step): array
    {
        // 1. Process variables in message
        $data = $conversation->bot_state_data ?? [];
        $message = $this->replaceVariables($step->message_text, $data, $conversation);

        // 2. Set conversation state
        if ($step->response_type !== 'action_only') {
            $conversation->bot_state = $step->step_key;
            $conversation->save();
        }

        // 3. Execute action if defined (only if action_only)
        if ($step->action_type && $step->response_type === 'action_only') {
            $actionResult = $this->executeAction($conversation, $step->action_type, $data, $step->action_config);
            if (isset($actionResult['_next_step'])) {
                $next = BotFlowStep::where('step_key', $actionResult['_next_step'])->first();
                if ($next)
                    return $this->executeStep($conversation, $next);
            }
            if (isset($actionResult['type'])) {
                return $this->attachMedia($actionResult, $step);
            }
        }

        // 4. Format string response based on type
        if ($step->response_type === 'buttons') {
            return $this->attachMedia($this->buttons($message, $step->options ?? []), $step);
        }

        if ($step->response_type === 'list') {
            return $this->attachMedia($this->listMenu($message, $step->options ?? []), $step);
        }

        return $this->attachMedia($this->text($message), $step);
    }

    private function attachMedia(array $response, BotFlowStep $step): array
    {
        if (!empty($step->media_path)) {
            $response['media_path'] = $step->media_path;
            $response['media_type'] = $step->media_type;
        }
        return $response;
    }

    private function executeAction(Conversation $conversation, string $actionType, array &$data, ?array $config = []): array
    {
        Log::info("BotEngine: Executing action", [
            'type' => $actionType,
            'config' => $config,
            'conversation_id' => $conversation->id,
            'contact_phone' => $conversation->contact->phone
        ]);
        if ($actionType === 'close_conversation') {
            $this->resetState($conversation);
            return []; // Handled by step message usually
        }

        if ($actionType === 'escalate_agent') {
            $this->resetState($conversation);
            $conversation->status = 'waiting_agent';
            $conversation->save();

            $reason = $config['reason'] ?? 'Solicitud desde flujo conversacional';
            $isCustomer = ($conversation->contact->type === 'customer');
            $phone = $conversation->contact->phone;
            $interest = $data['selected_plan'] ?? $data['plan_name'] ?? 'No especificado';
            $zip = $data['zip_code'] ?? 'N/A';

            // Auto-create lead if not a customer
            if (!$isCustomer) {
                SalesLead::firstOrCreate(
                [
                    'contact_id' => $conversation->contact->id,
                    'status' => 'pending'
                ],
                [
                    'conversation_id' => $conversation->id,
                    'plan_interest' => $interest,
                    'client_type' => $data['selected_category'] ?? 'Hogar',
                    'zip_code' => $zip,
                    'phone' => $phone,
                ]
                );
            }

            $messages = $conversation->messages()->orderByDesc('created_at')->take(8)->get()->reverse();
            $recentTxt = "";
            $lastClientMsg = "";
            $lastBotMsg = "";
            foreach ($messages as $msg) {
                $sender = $msg->direction === 'inbound' ? '👤 Cliente' : '🤖 Bot';
                $recentTxt .= "— {$sender}: {$msg->content}\n";
                if ($msg->direction === 'inbound') $lastClientMsg = $msg->content;
                else $lastBotMsg = $msg->content;
            }
            if (empty($recentTxt)) $recentTxt = "Sin mensajes previos.";

            if ($isCustomer) {
                $notifyData = [
                    'name' => $data['customer_name'] ?? $conversation->contact->name ?? 'Prueba',
                    'phone' => $phone,
                    'account' => $data['account_number'] ?? 'N/A',
                    'plan' => $data['plan_name'] ?? 'N/A',
                    'status' => 'Activo',
                    'reason' => $reason,
                    'summary' => $recentTxt
                ];
                TelegramService::notifyTechnicalAlert($notifyData);
            }
            else {
                $category = $data['selected_category'] ?? 'Hogar';
                
                // Check if it's a "No coverage" escalation
                if ($conversation->bot_state === 'no_coverage_empresa') {
                    $notifyData = [
                        'phone' => $phone,
                        'zip' => $data['zip_code'] ?? 'N/A',
                        'category' => 'Empresa',
                        'zones_available' => str_replace(["\n👉 ", "\n"], [' / ', ''], $data['coverage_zones'] ?? ''),
                        'colonia_input' => $data['colonia_input'] ?? 'NO aparece en zonas cubiertas'
                    ];
                    TelegramService::notifyProspectNoCoverage($notifyData);
                } else {
                    $notifyData = [
                        'phone' => $phone,
                        'zip' => $data['zip_code'] ?? 'N/A',
                        'colonia' => $data['neighborhood'] ?? 'Confirmada en zona de cobertura',
                        'category' => $category,
                        'plan' => $data['selected_plan'] ?? 'No especificado',
                        'price' => $data['plan_price'] ?? '',
                        'summary' => "— Verificó cobertura en CP " . ($data['zip_code'] ?? 'N/A') . " ✅\n— Interesado en planes " . strtolower($category) . "\n— Seleccionó el plan " . ($data['selected_plan'] ?? 'No especificado'),
                        'name' => $data['customer_name'] ?? $conversation->contact->name ?? 'Prospecto'
                    ];
                    TelegramService::notifyNewProspect($notifyData);
                }
            }

            return $this->text("👨‍💻 He desactivado el bot para esta conversación.\nUn asesor te atenderá personalmente.\n\n" . $this->workHours());
        }

        if ($actionType === 'validate_client') {
            $account = $data['ask_account'] ?? '';
            $name = $data['ask_name'] ?? '';

            $service = CustomerService::with('customer')->where('account_number', $account)->first();
            $match = false;

            if ($service && $service->customer) {
                similar_text($this->normalize($service->customer->name), $this->normalize($name), $sim);
                $match = $sim >= 60;
            }

            if ($match) {
                $data['customer_name'] = $service->customer->name;
                $data['account_number'] = $service->account_number;
                $data['plan_name'] = $service->plan_name;
                $data['customer_address'] = $service->address ?? $service->customer->address ?? '';
                $conversation->bot_state_data = $data;
                $conversation->save();

                // Mark contact as customer
                if ($conversation->contact) {
                    $conversation->contact->type = 'customer';
                    $conversation->contact->save();
                }

                return []; // Proceed to next step
            }
            else {
                $attempts = ($data['val_attempts'] ?? 0) + 1;
                $data['val_attempts'] = $attempts;
                $conversation->bot_state_data = $data;
                $conversation->save();

                if ($attempts >= 3) {
                    return ['_next_step' => 'client_val_failed']; // Not created in seeder but can be handled
                }

                return $this->text("❌ No encontré esa combinación de nombre y cuenta. Te quedan " . (3 - $attempts) . " intentos.");
            }
        }

        if ($actionType === 'check_coverage') {
            $zip = $data['ask_cp'] ?? '';
            $results = CoverageArea::where('is_active', true)->where('zip_code', $zip)->get();

            if ($results->count() > 0) {
                $data['zip_code'] = $zip;
                $neighborhoods = $results->pluck('neighborhood')->unique()->sort()->values();
                $data['coverage_zones'] = "\n👉 " . $neighborhoods->implode("\n👉 ");
                $conversation->bot_state_data = $data;
                $conversation->save();
                return ['_next_step' => 'confirm_neighborhood']; // Changed from check_coverage to match flow
            }
            else {
                $data['zip_code'] = $zip;
                $conversation->bot_state_data = $data;
                $conversation->save();

                return ['_next_step' => 'no_coverage']; // Matches the 'no_coverage' step in DB
            }
        }

        if ($actionType === 'create_lead') {
            try {
                $planCategory = $data['selected_category'] ?? 'Hogar';
                $planName = $data['selected_plan'] ?? 'No especificado';
                $zip = $data['zip_code'] ?? null;
                $phone = $conversation->contact->phone;

                // Avoid duplicate leads if triggered multiple times
                $exists = SalesLead::where('conversation_id', $conversation->id)
                    ->where('plan_interest', $planName)
                    ->exists();

                if (!$exists) {
                    SalesLead::create([
                        'contact_id' => $conversation->contact->id,
                        'conversation_id' => $conversation->id,
                        'plan_interest' => $planName,
                        'client_type' => $planCategory,
                        'zip_code' => $zip,
                        'phone' => $phone,
                        'status' => 'pending',
                    ]);

                    TelegramService::sendMessage("📡 *Nuevo Lead BGITAL*\n\n📱 Tel: {$phone}\n📦 Plan: {$planName}\n📍 CP: {$zip}");
                }
            } catch (\Exception $e) {
                Log::error("BotEngine: create_lead action failed: " . $e->getMessage());
            }

            $this->resetState($conversation);
            return [];
        }

        if ($actionType === 'show_plan_categories') {
            $categories = \App\Models\Plan::where('is_active', true)->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');
            if ($categories->isEmpty()) {
                return $this->text("Actualmente no hay planes configurados. Escribe *reiniciar* para volver al inicio.");
            }

            $options = [];
            foreach ($categories as $cat) {
                $options[] = [
                    'id' => 'cat_' . Str::slug($cat),
                    'title' => $cat,
                    'action' => 'show_plans',
                    'action_config' => ['category' => $cat]
                ];
            }

            $msg = "¿Qué tipo de plan estás buscando?";
            return count($options) <= 3 ? $this->buttons($msg, $options) : $this->listMenu($msg, $options);
        }

        if ($actionType === 'show_plans') {
            $category = $config['category'] ?? $data['selected_category'] ?? null;
            if ($category === 'Todas')
                $category = null;

            $data['selected_category'] = $category;
            $conversation->bot_state_data = $data;
            $conversation->save();

            $query = \App\Models\Plan::where('is_active', true);
            if ($category) {
                $query->where('category', $category);
            }
            $plans = $query->get();

            if ($plans->isEmpty()) {
                return $this->text("Actualmente no tenemos planes disponibles en esta categoría. Escribe *reiniciar* para volver al inicio.");
            }

            $msg = "Estos son los planes disponibles:\n\n";
            $options = [];
            foreach ($plans as $plan) {
                $msg .= "✅ *{$plan->name}* — {$plan->speed}\n";
                if ($plan->description) {
                    $msg .= "_{$plan->description}_\n";
                }
                $msg .= "💰 $" . number_format($plan->price, 2) . "/mes\n\n";

                $cleanName = str_replace('BGITAL ', '', $plan->name);
                $title = mb_substr($cleanName . ' - ' . $plan->speed, 0, 24);

                $options[] = [
                    'id' => 'plan_' . $plan->id,
                    'title' => $title,
                    'action' => 'select_plan',
                    'action_config' => ['plan_id' => $plan->id, 'plan_name' => $plan->name, 'price' => $plan->price]
                ];
            }

            $msg .= "¿Cuál de estos planes te interesa más?";

            if (count($options) <= 3) {
                return $this->buttons($msg, $options);
            }
            else {
                return $this->listMenu($msg, $options);
            }
        }

        if ($actionType === 'select_plan') {
            $data['selected_plan'] = $config['plan_name'] ?? 'Plan seleccionado';
            $data['plan_price'] = isset($config['price']) ? "$" . number_format($config['price'], 2) . "/mes" : '';
            $conversation->bot_state_data = $data;
            $conversation->save();

            // Notify immediately to avoid loss if user doesn't follow through
            try {
                $this->executeAction($conversation, 'create_lead', $data);
            } catch (\Exception $e) {
                Log::error("BotEngine: select_plan lead auto-trigger failed: " . $e->getMessage());
            }

            return ['_next_step' => 'confirm_plan'];
        }

        if ($actionType === 'set_category') {
            $data['selected_category'] = $config['category'] ?? 'Hogar';
            $conversation->bot_state_data = $data;
            $conversation->save();
            return ['_next_step' => 'show_plans'];
        }

        return [];
    }

    private function matchFlow(string $text): ?BotFlow
    {
        $flows = BotFlow::where('is_active', true)->orderByDesc('flow_priority')->orderBy('sort_order')->get();

        foreach ($flows as $flow) {
            $kws = is_array($flow->trigger_keywords) ? $flow->trigger_keywords : json_decode($flow->trigger_keywords, true);
            if (!$kws)
                continue;

            foreach ($kws as $kw) {
                $normalizedKw = $this->normalize($kw);
                if (mb_strlen($text) > 3 && $this->fuzzyMatch($text, $normalizedKw)) {
                    return $flow;
                }
                elseif (Str::contains($text, $normalizedKw) || Str::contains($normalizedKw, $text)) {
                    return $flow;
                }
            }
        }

        return null;
    }

    private function text(string $msg): array
    {
        return ['type' => 'text', 'text' => $msg];
    }

    private function buttons(string $msg, array $options): array
    {
        $btns = [];
        foreach (array_slice($options, 0, 3) as $opt) {
            $btns[] = ['type' => 'reply', 'reply' => ['id' => $opt['id'], 'title' => mb_substr($opt['title'], 0, 20)]];
        }
        return ['type' => 'text', 'text' => $msg, 'buttons' => $btns];
    }

    private function listMenu(string $msg, array $options): array
    {
        $rows = [];
        foreach (array_slice($options, 0, 10) as $opt) {
            $rows[] = [
                'id' => mb_substr($opt['id'], 0, 200),
                'title' => mb_substr($opt['title'], 0, 24),
                'description' => mb_substr($opt['description'] ?? '', 0, 72)
            ];
        }

        return [
            'type' => 'list',
            'text' => $msg,
            'list_sections' => [['title' => 'Opciones', 'rows' => $rows]],
            'list_button_text' => 'Ver Opciones'
        ];
    }

    private function replaceVariables(string $text, array $data, Conversation $conversation): string
    {
        $text = str_replace('{{customer_name}}', $data['customer_name'] ?? $conversation->contact->name ?? '', $text);
        $text = str_replace('{{account_number}}', $data['account_number'] ?? '', $text);
        $text = str_replace('{{plan_name}}', $data['plan_name'] ?? '', $text);
        $text = str_replace('{{zip_code}}', $data['zip_code'] ?? '', $text);
        $text = str_replace('{{coverage_zones}}', $data['coverage_zones'] ?? '', $text);
        $text = str_replace('{{selected_plan}}', $data['selected_plan'] ?? '', $text);
        $text = str_replace('{{ask_name}}', $data['ask_name'] ?? '', $text);

        if (!Str::contains($text, 'reiniciar')) {
            $text .= "\n\n_(Puedes escribir *reiniciar* en cualquier momento para empezar una nueva conversación)_";
        }

        return $text;
    }

    private function resetState(Conversation $c): void
    {
        $c->bot_state = null;
        $c->bot_state_data = null;
        $c->save();
    }

    private function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        // Preserve alphanumeric, underscores and spaces
        return preg_replace('/[^a-z0-9_\s]/', '', $t);
    }

    private function fuzzyMatch(string $input, string $target): bool
    {
        if (empty($input) || empty($target))
            return false;

        $inputWords = explode(' ', $input);
        foreach ($inputWords as $word) {
            if (mb_strlen($word) > 3) {
                similar_text($word, $target, $sim);
                if ($sim >= 70 || levenshtein($word, $target) <= 2)
                    return true;
            }
        }

        similar_text($input, $target, $simTotal);
        return $simTotal >= 70 || Str::contains($target, $input);
    }

    private function workHours(): string
    {
        return "Pronto nos pondremos en contacto contigo.";
    }
}
