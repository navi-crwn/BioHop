<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AbuseFlag;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Support\BioRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Manage": the prices, stock and goal figures of every bio page of the member in one place, without opening the editor,
 * plus the results of polls and forms to read. Only the member's own blocks; changes are merged into the block's
 * settings (the editor's own save replaces them whole) and a block that changed meanwhile is never overwritten.
 */
class BioManageController extends Controller
{
    public function index(Request $request)
    {
        $pages = $request->user()->bioPages()->orderBy('id')
            ->with(['links' => fn ($q) => $q->notArchived()->whereIn('type', ['product', 'goal', 'poll', 'form'])->withCount('leads')->orderBy('order')->orderBy('id')])
            ->get();

        return view('bio::manage', ['pages' => $pages->filter(fn ($p) => $p->links->isNotEmpty())->values(), 'hasPages' => $pages->isNotEmpty()]);
    }

    /** The changes of one product or goal block. 409 when the block is not what the page showed, 404 for anybody else's. */
    public function update(Request $request, BioLink $bioLink): JsonResponse
    {
        $page = $bioLink->bioPage;
        abort_unless($page && $request->user()->can('update', $page) && in_array($bioLink->type, ['product', 'goal'], true), 404);

        $rules = ['base_version' => 'required|string|max:64'];
        if ($bioLink->type === 'product') {
            $rules += [
                'title' => 'required|string|max:100',
                'is_active' => 'required|boolean',
                'settings' => 'required|array',
                'settings.price' => 'nullable|numeric|min:0|max:999999999',
                'settings.compare_price' => 'nullable|numeric|min:0|max:999999999',
                'settings.stock' => 'nullable|integer|min:0|max:999999',
                'settings.sold_out' => 'required|boolean',
            ];
        } else {
            $rules += [
                'settings' => 'required|array',
                'settings.current' => 'required|numeric|min:0|max:999999999999',
                'settings.target' => 'required|numeric|gt:0|max:999999999999',
                'settings.unit' => 'nullable|string|max:20',
            ];
        }
        $data = $request->validate($rules);
        // The same safety checks as the editor's save (blocked phrases, NoteHop key links), before anything is written.
        if ($bioLink->type === 'product') {
            app(BioLinkController::class)->assertSafe(['title' => trim($data['title'])]);
        }
        $keys = $bioLink->type === 'product' ? ['price', 'compare_price', 'stock', 'sold_out'] : ['current', 'target', 'unit'];

        // Check and write in one step on the locked row, so two saves at the same moment (or an editor save in between) cannot both pass.
        $conflict = response()->json(['success' => false, 'conflict' => true, 'message' => __('This block was changed somewhere else (for example in the editor). Reload this page to see the latest values; nothing was saved.')], 409);
        $result = DB::transaction(function () use ($bioLink, $page, $data, $keys, $conflict) {
            $block = BioLink::query()->whereKey($bioLink->getKey())->lockForUpdate()->first();
            if (! $block || $data['base_version'] !== $block->contentVersion()) {
                return $conflict;
            }
            if ($block->type === 'product' && $data['is_active'] && ! $block->is_active && AbuseFlag::locks('bio_link', $block->id)) {
                return response()->json(['success' => false, 'message' => __('This block was disabled by moderation.')], 422);
            }
            // Only the figures this page owns are written, the rest of settings (currency, variants, ...) stays.
            $settings = $block->settings ?? [];
            foreach ($keys as $key) {
                $value = $data['settings'][$key] ?? null;
                if ($value === null || $value === '') {
                    unset($settings[$key]);
                } else {
                    // numbers are stored as numbers, like the editor's own figures, so 12 and "12" never differ
                    $settings[$key] = $key === 'unit' || is_bool($value) ? $value : $value + 0;
                }
            }
            BioRevisions::maybeSnapshot($page);
            $block->update(($block->type === 'product' ? ['title' => trim($data['title']), 'is_active' => (bool) $data['is_active']] : []) + ['settings' => $settings]);
            // The page counts as changed (an editor tab open on it meets a conflict) and a reader's cached copy goes (the edge copy is purged by the model).
            $page->touch();
            Cache::forget('bio-stateless:'.strtolower($page->slug));

            return $block->fresh();
        });
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json(['success' => true, 'version' => $result->contentVersion(), 'message' => __('Saved')]);
    }
}
