<?php

namespace App\Bio;

use App\Support\PhishingShape;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Safety\LinkSafety;

/**
 * The bio package's UrlSafety contract, answered by hop/safety's LinkSafety (shared rules and blacklist), plus the
 * shape rules BioHop keeps for itself (App\Support\PhishingShape): a bio block must not become the way around a
 * block LinkHop already applies to short links.
 */
class SafetyAdapter implements UrlSafety
{
    /** The shape signals worth naming on a flag, so abuse:scan and the audit trail say why. */
    private const SHAPE_SIGNALS = ['kit_host', 'kit_path', 'random_path', 'tracking_params'];

    public function __construct(private readonly LinkSafety $safety) {}

    public function rejectionReason(?string $url, string $text = '', array $schemes = self::WEB_SCHEMES): ?string
    {
        $reason = $this->safety->rejectionReason($url, $text, $schemes);
        if ($reason !== null) {
            return $reason;
        }
        // Only web addresses carry the kit shape; mailto: and tel: have no host to read.
        if ($this->scheme((string) $url) !== null && in_array($this->scheme((string) $url), self::WEB_SCHEMES, true)
            && PhishingShape::assess($url)['hit']) {
            return 'This destination is not allowed due to our security policy.';
        }

        return null;
    }

    public function assess(?string $url, string $text = '', bool $remote = false): array
    {
        $result = $this->safety->assess($url, $text, $remote);
        $shape = PhishingShape::assess($url);
        if ($shape['hit']) {
            $result['signals'][] = 'phishing_shape';
            $result['signals'] = array_values(array_unique(array_merge(
                $result['signals'],
                array_intersect($shape['signals'], self::SHAPE_SIGNALS),
            )));
            // The shape alone is a block, as it is in LinkHop: the weights of the parts are too small to reach
            // safety.thresholds.disable on their own (random_path and tracking_params are unweighted there).
            $result['score'] = max($result['score'], (int) config('safety.thresholds.disable', 60));
        }

        return $result;
    }

    public function host(?string $url): ?string
    {
        return $this->safety->host($url);
    }

    /** Drop the cached blacklist, so a scan reads the table after another app changed it. */
    public function forgetBlacklist(): void
    {
        $this->safety->forgetBlacklist();
    }

    public function scheme(?string $url): ?string
    {
        return $this->safety->scheme($url);
    }
}
