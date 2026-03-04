<?php

namespace App\Services;

use App\Models\BotFlow;
use App\Models\Conversation;
use App\Models\Ticket;
use Illuminate\Support\Str;

class BotEngineService
{
    /**
     * Handle incoming message with State Machine logic
     * Returns an array mapping of the action to take.
     */
    public function handleSequence(Conversation $conversation, string $text): array
    {
        $normalizedText = $this->normalize($text);

        // 1. Check if we have an active state

        // State: Creating a ticket
        if ($conversation->bot_state === 'awaiting_ticket_description') {
            // User sent their description
            $ticket = Ticket::create([
                'contact_id' => $conversation->contact_id,
                'subject' => 'Reporte desde Bot',
                'description' => $text,
                'status' => 'open',
                'priority' => 'medium'
            ]);

            // Reset state
            $this->resetState($conversation);

            return [
                'type' => 'text',
                'text' => "✅ *Tu ticket de atención ha sido generado exitosamente.*\n\n*Folio:* #T-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . "\n\nUn asesor revisará tu caso y se pondrá en contacto contigo pronto. ¿Hay algo más en lo que podamos ayudarte?",
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'btn_menu', 'title' => 'Volver al Menú']]
                ]
            ];
        }

        // State: Validating if troubleshooting solved the issue
        if ($conversation->bot_state === 'awaiting_troubleshooting_validation') {
            if (in_array($normalizedText, ['si', 'afirmativo', 'funciono', 'listo', 'ya quedo'])) {
                $this->resetState($conversation);
                return [
                    'type' => 'text',
                    'text' => "¡Excelente! Me da gusto haberte ayudado. 😊 Si necesitas algo más, solo escribe *'Menú'*. ¡Que tengas un gran día!",
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'btn_menu', 'title' => 'Volver al Menú']]
                    ]
                ];
            }

            if (in_array($normalizedText, ['no', 'negativo', 'sigue igual', 'continua'])) {
                // If they have more steps, continue. If not, offer ticket.
                $lastFlowId = $conversation->bot_state_data['last_troubleshooting_id'] ?? null;
                $nextStep = BotFlow::where('follow_up_to', $lastFlowId)->first();

                if ($nextStep) {
                    return $this->processFlow($conversation, $nextStep);
                }

                // No more steps, offer ticket
                $conversation->bot_state = 'awaiting_ticket_confirmation';
                $conversation->save();

                return [
                    'type' => 'text',
                    'text' => "Lamento que los pasos anteriores no funcionaran. 😔\n\n¿Deseas que genere un *Ticket de Soporte Técnico* para que un experto revise tu línea personalmente?",
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'confirm_ticket', 'title' => 'Sí, crear Ticket']],
                        ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hablar con asesor']],
                        ['type' => 'reply', 'reply' => ['id' => 'menu', 'title' => 'Volver al Menú']]
                    ]
                ];
            }
        }

        // State: Confirmation to create a ticket
        if ($conversation->bot_state === 'awaiting_ticket_confirmation') {
            if (in_array($normalizedText, ['si', 'confirmar_ticket', 'crear ticket'])) {
                $conversation->bot_state = 'awaiting_ticket_description';
                $conversation->save();
                return [
                    'type' => 'text',
                    'text' => "Entendido. Por favor, descríbeme brevemente el problema para que el técnico tenga el contexto necesario:",
                ];
            }
        }

        // 2. Global "Reset" commands
        if (in_array($normalizedText, ['menu', 'inicio', 'cancelar', 'salir'])) {
            $this->resetState($conversation);
            return $this->getMenuAction();
        }

        // 3. Normal Keyword Matching
        $flow = $this->match($text);

        if ($flow) {
            return $this->processFlow($conversation, $flow);
        }

        // 4. Default Fallback
        return [
            'type' => 'text',
            'text' => "Lo siento, no he logrado entender tu respuesta. 🤖\nSi necesitas asistencia humana escribe *'Agente'* o escribe *'Menú'* para ver las opciones principales.",
            'buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'btn_menu', 'title' => 'Ver Menú']],
                ['type' => 'reply', 'reply' => ['id' => 'btn_agent', 'title' => 'Hablar con humano']]
            ]
        ];
    }

    protected function processFlow(Conversation $conversation, BotFlow $flow): array
    {
        // Handle ticket_creation trigger
        if ($flow->response_type === 'ticket_creation') {
            $conversation->bot_state = 'awaiting_ticket_description';
            $conversation->save();

            return [
                'type' => 'text',
                'text' => $flow->response_text ?: "Entendido. Para abrir un reporte técnico, por favor descríbeme detalladamente el problema que estás experimentando:",
            ];
        }

        // Handle troubleshooting steps
        if ($flow->response_type === 'troubleshooting') {
            $conversation->bot_state = 'awaiting_troubleshooting_validation';
            $conversation->bot_state_data = ['last_troubleshooting_id' => $flow->id];
            $conversation->save();

            return [
                'type' => 'text',
                'text' => $flow->response_text,
                'media_path' => $flow->media_path,
                'media_type' => $flow->media_type,
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'solved_yes', 'title' => 'Ya funcionó 👍']],
                    ['type' => 'reply', 'reply' => ['id' => 'solved_no', 'title' => 'Sigue sin funcionar 👎']],
                ]
            ];
        }

        // Normal flows
        return [
            'type' => $flow->response_type,
            'text' => $flow->response_text,
            'buttons' => $flow->response_buttons,
            'media_path' => $flow->media_path,
            'media_type' => $flow->media_type,
        ];
    }

    protected function resetState(Conversation $conversation)
    {
        $conversation->bot_state = null;
        $conversation->bot_state_data = null;
        $conversation->save();
    }

    /**
     * Match incoming message text to a bot flow.
     */
    public function match (string $text): ?BotFlow
    {
        $normalized = $this->normalize($text);
        $words = preg_split('/\s+/', $normalized);

        $flows = BotFlow::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($flows as $flow) {
            // Support trigger_keywords being string or array due to JSON cast
            $keywords = is_array($flow->trigger_keywords)
                ? $flow->trigger_keywords
                : json_decode($flow->trigger_keywords, true) ?? [];

            foreach ($keywords as $keyword) {
                $normalizedKeyword = $this->normalize($keyword);

                if (in_array($normalizedKeyword, $words) || Str::contains($normalized, $normalizedKeyword)) {
                    return $flow;
                }
            }
        }

        return null; // Ensure null is returned if nothing matches
    }

    protected function getMenuAction()
    {
        // Try to find a global menu flow
        $menuFlow = $this->match('menu');
        if ($menuFlow) {
            return [
                'type' => $menuFlow->response_type,
                'text' => $menuFlow->response_text,
                'buttons' => $menuFlow->response_buttons,
            ];
        }

        // Default menu
        return [
            'type' => 'buttons',
            'text' => "📍 *Menú Principal*\nSelecciona una opción para que pueda ayudarte:",
            'buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'support', 'title' => 'Soporte Técnico']],
                ['type' => 'reply', 'reply' => ['id' => 'sales', 'title' => 'Ventas']],
                ['type' => 'reply', 'reply' => ['id' => 'agent', 'title' => 'Hablar con un Agente']],
            ]
        ];
    }

    /**
     * Normalize text: lowercase, remove accents, trim.
     */
    protected function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ];

        $text = strtr($text, $accents);
        $text = preg_replace('/[^\w\s]/', '', $text);

        return $text;
    }
}
