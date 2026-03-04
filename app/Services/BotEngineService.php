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
        // 1. Check if we have an active state
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
            $conversation->bot_state = null;
            $conversation->bot_state_data = null;
            $conversation->save();

            return [
                'type' => 'text',
                'text' => "✅ *Tu ticket de atención ha sido generado exitosamente.*\n\n*Folio:* #T-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . "\n\nUn asesor revisará tu caso y se pondrá en contacto contigo pronto. ¿Hay algo más en lo que podamos ayudarte?",
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'btn_menu', 'title' => 'Volver al Menú']]
                ]
            ];
        }

        // 2. Global "Reset" commands
        if (in_array($this->normalize($text), ['menu', 'inicio', 'cancelar', 'salir'])) {
            $conversation->bot_state = null;
            $conversation->bot_state_data = null;
            $conversation->save();
            return $this->getMenuAction();
        }

        // 3. Normal Keyword Matching
        $flow = $this->match($text);

        if ($flow) {
            // Process ticket_creation trigger
            if ($flow->response_type === 'ticket_creation') {
                $conversation->bot_state = 'awaiting_ticket_description';
                $conversation->save();

                return [
                    'type' => 'text',
                    'text' => $flow->response_text ?: "Entendido. Para abrir un reporte técnico, por favor descríbeme detalladamente el problema que estás experimentando:",
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
