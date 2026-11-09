<?php

namespace App\Http\Controllers;

use App\Support\Reminders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** POST /reminders/dismiss: the "Hide" button of a reminder card (hop-ui.js posts {key}). */
class ReminderController extends Controller
{
    public function dismiss(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', Rule::in(Reminders::KEYS)]]);
        $again = Reminders::dismiss((int) $request->user()->id, $data['key']);

        return response()->json(['ok' => true, 'shows_again_at' => $again->toIso8601String()])->header('Cache-Control', 'no-store');
    }
}
