<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use Hop\Bio\Exceptions\PixelHopUnavailable;
use Hop\Bio\Support\PixelHopMedia;
use Illuminate\Http\Request;

/** Files from the other HOP apps for the editor's pickers. For now: PixelHop pictures and PDFs. */
class BioMediaController extends Controller
{
    public function pixelhop(Request $request, PixelHopMedia $media)
    {
        abort_unless(PixelHopMedia::enabled(), 404);
        $data = $request->validate([
            'type' => 'nullable|in:image,pdf',
            'cursor' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]*$/'],
            'limit' => 'nullable|integer|min:1|max:100',
        ]);
        try {
            $list = $media->list($request->user(), $data['type'] ?? 'image', (string) ($data['cursor'] ?? ''), (int) ($data['limit'] ?? 40));
        } catch (PixelHopUnavailable $e) {
            if ($e->reason === 'no_account') {
                return response()->json(['code' => 'no_account', 'message' => __('Sign in to PixelHop once with your HOP ID; your pictures will then show up here.')], 404);
            }
            report($e);

            return response()->json(['code' => 'unavailable', 'message' => __('PixelHop is not reachable right now. You can still upload a picture.')], 503, ['Retry-After' => '60']);
        }

        return response()->json($list)->header('Cache-Control', 'no-store');
    }
}
