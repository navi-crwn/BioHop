<?php

namespace App\Http\Controllers;

use App\Models\AbuseReport;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbuseReportController extends Controller
{
    public function __construct(private readonly TurnstileService $turnstile) {}

    public function create(Request $request): View
    {
        $slug = (string) $request->query('slug', '');
        $slug = preg_match('/^[A-Za-z0-9_-]{1,120}$/', $slug) ? $slug : '';

        return view('report-abuse', [
            'slug' => $slug,
            'url' => $slug !== '' ? public_url('b/'.$slug) : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()
                ->withErrors(['turnstile' => 'Security verification failed, please try again.'])
                ->withInput();
        }
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'url' => ['nullable', 'url', 'max:500'],
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $ip = $request->ip();
        $recent = AbuseReport::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();
        if ($recent >= 3) {
            return back()->withErrors(['reason' => 'Too many reports from your network, please try again later.'])->withInput();
        }
        AbuseReport::create([...$data, 'ip_address' => $ip, 'status' => 'open']);

        return back()->with('status', 'Thank you. Your report was sent to the moderators.');
    }
}
