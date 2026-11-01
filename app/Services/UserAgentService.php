<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;
use Hop\Bio\Contracts\UserAgentParser;
use Illuminate\Support\Facades\Cache;

/**
 * User-agent parsing via matomo/device-detector (browsers, OS, device types, bots).
 */
class UserAgentService implements UserAgentParser
{
    private array $memo = [];

    public function parse(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['device' => 'Unknown', 'browser' => 'Unknown', 'os' => 'Unknown', 'bot' => false];
        }

        // A cold parse takes a few hundred milliseconds and every request starts cold, which a visitor feels as a slow
        // redirect on a link click. The same browsers come back all day, so the result is kept in the cache.
        return $this->memo[$userAgent] ??= Cache::remember('ua:'.sha1($userAgent), now()->addDays(7), fn () => $this->detect($userAgent));
    }

    private function detect(string $userAgent): array
    {
        $dd = new DeviceDetector($userAgent);
        $dd->parse();
        if ($dd->isBot()) {
            return ['device' => 'Bot', 'browser' => $dd->getBot()['name'] ?? 'Bot', 'os' => 'Unknown', 'bot' => true];
        }
        $device = match (true) {
            $dd->isTablet() => 'Tablet',
            $dd->isMobile() => 'Mobile',
            $dd->isDesktop() => 'Desktop',
            default => ucfirst($dd->getDeviceName() ?: 'Unknown'),
        };

        return [
            'device' => $device,
            'browser' => $dd->getClient('name') ?: 'Unknown',
            'os' => $dd->getOs('name') ?: 'Unknown',
            'bot' => false,
        ];
    }
}
