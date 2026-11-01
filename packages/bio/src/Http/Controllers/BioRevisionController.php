<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioRevision;
use Hop\Bio\Support\BioRevisions;
use Illuminate\Http\Request;

class BioRevisionController extends Controller
{
    public function index(BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);

        return response()->json($bioPage->revisions()->latest('id')->get(['id', 'label', 'blocks_count', 'created_at'])->map(fn ($r) => [
            'id' => $r->id,
            'label' => $r->label,
            'blocks_count' => $r->blocks_count,
            'created_at' => $r->created_at?->toIso8601String(),
        ]));
    }

    public function store(Request $request, BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        $data = $request->validate(['label' => 'nullable|string|max:60']);
        $revision = BioRevisions::snapshot($bioPage, trim((string) ($data['label'] ?? '')) ?: null);

        return response()->json(['success' => true, 'id' => $revision->id]);
    }

    public function restore(BioPage $bioPage, BioRevision $revision)
    {
        $this->authorize('update', $bioPage);
        BioRevisions::restore($bioPage, $revision);

        return response()->json(['success' => true]);
    }
}
