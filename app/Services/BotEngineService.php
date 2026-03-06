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
        if (in_array($normalizedText, ['menu', 'inicio', 'menú', 'salir'])) {
            $this->resetState($conversation);
            $conversation->refresh();
        }

        // 3. Current active step execution
        if ($conversation->bot_state) {
            $currentStep = BotFlowStep::where('step_key', $conversation->bot_state)->first();
            if ($currentStep) {
                return $this->processStepInput($conversation, $currentStep, $text, $normalizedText);
            }
        }

        // 4. Match Flow by Keywords
        $flow = $this->matchFlow($normalizedText);
        if ($flow) {
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

        return $this->text("🤔 No entendí tu mensaje. Escribe *Menú* para reiniciar.");
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

        foreach ($options as $opt) {
            if ($normalizedText === mb_strtolower(trim($opt['id'])) || $this->fuzzyMatch($normalizedText, mb_strtolower(trim($opt['title'])))) {
                $selectedOption = $opt;
                break;
            }
        }

        if ($selectedOption) {
            if (!empty($selectedOption['action'])) {
                return $this->executeAction($conversation, $selectedOption['action'], $data);
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

        // 3. Format response based on type
        if ($step->response_type === 'text' || $step->response_type === 'action_only') {
            if ($step->action_type) {
                $actionResult = $this->executeAction($conversation, $step->action_type, $data, $step->action_config);
                if (isset($actionResult['_next_step'])) {
                    $next = BotFlowStep::where('step_key', $actionResult['_next_step'])->first();
                    if ($next)
                        return $this->executeStep($conversation, $next);
                }
                if (isset($actionResult['type']) && $step->response_type === 'action_only') {
                    return $actionResult;
                }
            }
            return $this->text($message);
        }

        if ($step->response_type === 'buttons') {
            return $this->buttons($message, $step->options ?? []);
        }

        if ($step->response_type === 'list') {
            return $this->listMenu($message, $step->options ?? []);
        }

        return $this->text($message);
    }

    private function executeAction(Conversation $conversation, string $actionType, array &$data, ?array $config = []): array
    {
        if ($actionType === 'close_conversation') {
            $this->resetState($conversation);
            return []; // Handled by step message usually
        }

        if ($actionType === 'escalate_agent') {
            $this->resetState($conversation);
            $conversation->status = 'waiting_agent';
            $conversation->save();

            $reason = $config['reason'] ?? 'Solicitud desde flujo conversacional';
            $name = $data['customer_name'] ?? $conversation->contact->name ?? $conversation->contact->phone;
            TelegramService::sendMessage("👨‍💻 *Escalación a Asesor*\n\n👤 {$name}\n📱 {$conversation->contact->phone}\n📝 {$reason}");

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
                $conversation->bot_state_data = $data;
                $conversation->save();
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
                $data['coverage_zones'] = $results->pluck('neighborhood')->unique()->take(4)->implode(', ');
                $conversation->bot_state_data = $data;
                $conversation->save();
                return []; // Go to success next_step
            }
            else {
                $data['zip_code'] = $zip;
                $conversation->bot_state_data = $data;
                $conversation->save();
                TelegramService::notifyNoCoverageLead($conversation->contact->phone, $zip, 'N/A');
                return ['_next_step' => 'no_coverage']; // Override routing to the no coverage step!
            }
        }

        if ($actionType === 'create_lead') {
            $planCategory = $data['selected_category'] ?? 'Hogar';
            $planName = $data['selected_plan'] ?? 'No especificado';
            $zip = $data['zip_code'] ?? null;
            $phone = $conversation->contact->phone;

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
            $this->resetState($conversation);
            return [];
        }

        if ($actionType === 'show_plan_categories') {
            // Usually handled with buttons, this returns action_only. 
            // Our CRM step for 'show_categories' actually has `next_step_default => show_plans`.
            return [];
        }

        if ($actionType === 'show_plans') {
            // Also handled in list menu for planes
            return [];
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
                if (Str::contains($text, mb_strtolower(trim($kw)))) {
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
            $btns[] = ['type' => 'reply', 'reply' => ['id' => $opt['id'], 'title' => substr($opt['title'], 0, 20)]];
        }
        return ['type' => 'text', 'text' => $msg, 'buttons' => $btns];
    }

    private function listMenu(string $msg, array $options): array
    {
        $rows = [];
        foreach (array_slice($options, 0, 10) as $opt) {
            $rows[] = [
                'id' => substr($opt['id'], 0, 200),
                'title' => substr($opt['title'], 0, 24),
                'description' => substr($opt['description'] ?? '', 0, 72)
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
        return preg_replace('/[^\w\s]/', '', $t);
    }

    private function fuzzyMatch(string $input, string $target): bool
    {
        similar_text($input, $target, $sim);
        return $sim >= 75 || Str::contains($target, $input);
    }

    private function workHours(): string
    {
        return "Pronto nos pondremos en contacto contigo.";
    }
}
