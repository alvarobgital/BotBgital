<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BotFlow;
use App\Models\BotFlowStep;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    public function index(Request $request)
    {
        $query = BotFlow::with('steps')->orderByDesc('flow_priority')->orderBy('sort_order');

        if ($request->filled('flow_type')) {
            $query->where('flow_type', $request->flow_type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->parseJsonFields($request);

        $validated = $request->validate([
            'slug' => 'nullable|string|max:100|unique:bot_flows,slug',
            'category' => 'required|string|max:255',
            'flow_type' => 'required|in:main,keyword',
            'description' => 'nullable|string|max:500',
            'trigger_keywords' => 'nullable|array',
            'trigger_keywords.*' => 'string',
            'response_text' => 'nullable|string',
            'response_type' => 'nullable|in:text,buttons,list,handoff',
            'response_buttons' => 'nullable|array|max:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'flow_priority' => 'integer',
        ]);

        // Auto-generate slug
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['category']);
            $count = BotFlow::where('slug', 'like', $validated['slug'] . '%')->count();
            if ($count > 0)
                $validated['slug'] .= '-' . ($count + 1);
        }

        $flow = BotFlow::create($validated);
        return response()->json($flow->load('steps'), 201);
    }

    public function show(BotFlow $flow)
    {
        return response()->json($flow->load('steps'));
    }

    public function update(Request $request, BotFlow $flow)
    {
        $this->parseJsonFields($request);

        $validated = $request->validate([
            'slug' => "nullable|string|max:100|unique:bot_flows,slug,{$flow->id}",
            'category' => 'sometimes|string|max:255',
            'flow_type' => 'sometimes|in:main,keyword',
            'description' => 'nullable|string|max:500',
            'trigger_keywords' => 'nullable|array',
            'trigger_keywords.*' => 'string',
            'response_text' => 'nullable|string',
            'response_type' => 'nullable|in:text,buttons,list,handoff',
            'response_buttons' => 'nullable|array|max:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'flow_priority' => 'integer',
        ]);

        $flow->update($validated);
        return response()->json($flow->load('steps'));
    }

    public function destroy(BotFlow $flow)
    {
        if ($flow->is_system_flow) {
            return response()->json(['message' => 'No se puede eliminar un flujo del sistema.'], 403);
        }
        $flow->delete(); // cascade deletes steps
        return response()->json(['status' => 'deleted']);
    }

    public function toggleActive(BotFlow $flow)
    {
        $flow->update(['is_active' => !$flow->is_active]);
        return response()->json($flow);
    }

    // ═══════════════════════════════════════
    //  STEP CRUD
    // ═══════════════════════════════════════

    public function storeStep(Request $request, BotFlow $flow)
    {
        $this->parseStepJson($request);

        $validated = $request->validate([
            'step_key' => 'required|string|max:100',
            'message_text' => 'required|string',
            'response_type' => 'required|in:text,buttons,list,input,action_only',
            'options' => 'nullable|array',
            'action_type' => 'nullable|string|max:100',
            'action_config' => 'nullable|array',
            'next_step_default' => 'nullable|string|max:100',
            'input_validation' => 'nullable|in:none,zip_code,account_number,phone,name,text',
            'retry_limit' => 'integer|min:0|max:10',
            'is_entry_point' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['bot_flow_id'] = $flow->id;

        // If entry point, unset others
        if (!empty($validated['is_entry_point'])) {
            BotFlowStep::where('bot_flow_id', $flow->id)->update(['is_entry_point' => false]);
        }

        $step = BotFlowStep::create($validated);
        return response()->json($step, 201);
    }

    public function updateStep(Request $request, BotFlowStep $step)
    {
        $this->parseStepJson($request);

        $validated = $request->validate([
            'step_key' => "sometimes|string|max:100",
            'message_text' => 'sometimes|string',
            'response_type' => 'sometimes|in:text,buttons,list,input,action_only',
            'options' => 'nullable|array',
            'action_type' => 'nullable|string|max:100',
            'action_config' => 'nullable|array',
            'next_step_default' => 'nullable|string|max:100',
            'input_validation' => 'nullable|in:none,zip_code,account_number,phone,name,text',
            'retry_limit' => 'integer|min:0|max:10',
            'is_entry_point' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if (!empty($validated['is_entry_point'])) {
            BotFlowStep::where('bot_flow_id', $step->bot_flow_id)->where('id', '!=', $step->id)->update(['is_entry_point' => false]);
        }

        $step->update($validated);
        return response()->json($step);
    }

    public function destroyStep(BotFlowStep $step)
    {
        $step->delete();
        return response()->json(['status' => 'deleted']);
    }

    // ═══════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════

    protected function parseJsonFields(Request $request)
    {
        foreach (['trigger_keywords', 'response_buttons'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => json_decode($request->input($field), true)]);
            }
        }
    }

    protected function parseStepJson(Request $request)
    {
        foreach (['options', 'action_config'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => json_decode($request->input($field), true)]);
            }
        }
    }
}
