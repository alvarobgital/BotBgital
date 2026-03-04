<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BotFlow;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    public function index()
    {
        $flows = BotFlow::orderBy('sort_order')->get();
        return response()->json($flows);
    }

    public function store(Request $request)
    {
        $this->parseJsonFields($request);

        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'trigger_keywords' => 'required|array|min:1',
            'trigger_keywords.*' => 'string',
            'response_text' => 'required|string',
            'response_type' => 'required|in:text,buttons,list,handoff,ticket_creation',
            'response_buttons' => 'nullable|array|max:3',
            'response_buttons.*.id' => 'required_with:response_buttons|string',
            'response_buttons.*.title' => 'required_with:response_buttons|string|max:20',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf,mp4|max:10240',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $flowData = tap($validated, function (&$data) use ($request) {
            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $data['media_path'] = $file->store('flows', 'public');
                $data['media_type'] = $this->determineMediaType($file->getMimeType());
            }
            unset($data['media_file']);
        });

        $flow = BotFlow::create($flowData);
        return response()->json($flow, 201);
    }

    public function show(BotFlow $flow)
    {
        return response()->json($flow);
    }

    public function update(Request $request, BotFlow $flow)
    {
        $this->parseJsonFields($request);

        $validated = $request->validate([
            'category' => 'sometimes|string|max:255',
            'trigger_keywords' => 'sometimes|array|min:1',
            'trigger_keywords.*' => 'string',
            'response_text' => 'sometimes|string',
            'response_type' => 'sometimes|in:text,buttons,list,handoff,ticket_creation',
            'response_buttons' => 'nullable|array|max:3',
            'response_buttons.*.id' => 'required_with:response_buttons|string',
            'response_buttons.*.title' => 'required_with:response_buttons|string|max:20',
            'media_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf,mp4|max:10240',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $flowData = tap($validated, function (&$data) use ($request) {
            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $data['media_path'] = $file->store('flows', 'public');
                $data['media_type'] = $this->determineMediaType($file->getMimeType());
            }
            unset($data['media_file']);
        });

        $flow->update($flowData);
        return response()->json($flow);
    }

    protected function parseJsonFields(Request $request)
    {
        // When sending multipart/form-data, arrays arrive as JSON strings
        if (is_string($request->input('trigger_keywords'))) {
            $request->merge(['trigger_keywords' => json_decode($request->input('trigger_keywords'), true)]);
        }
        if (is_string($request->input('response_buttons'))) {
            $request->merge(['response_buttons' => json_decode($request->input('response_buttons'), true)]);
        }
    }

    protected function determineMediaType($mime)
    {
        if (str_starts_with($mime, 'image/'))
            return 'image';
        if (str_starts_with($mime, 'video/'))
            return 'video';
        if (str_starts_with($mime, 'audio/'))
            return 'audio';
        return 'document';
    }

    public function destroy(BotFlow $flow)
    {
        $flow->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function toggleActive(BotFlow $flow)
    {
        $flow->update(['is_active' => !$flow->is_active]);
        return response()->json($flow);
    }
}
