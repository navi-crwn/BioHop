<?php

namespace Hop\Bio\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads a product page's public tags (Open Graph, product price, JSON-LD) so a member can fill a product block from its
 * address. Shops that block bots simply give nothing: it is a convenience, never a requirement. The page is fetched with
 * a 2.5 second limit, and a site that failed is not asked again for ten minutes.
 */
class ProductMeta
{
    public const FAIL_SECONDS = 600;

    /** @return array{title:?string, description:?string, image:?string, price:?float, currency:?string} */
    public static function fetch(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (Cache::has('meta-fail:'.$host)) {
            throw new \RuntimeException('This site did not answer a moment ago.');
        }
        try {
            $page = SafeFetch::get($url, 600_000, 2.5, 'text/html,application/xhtml+xml');
            if (! str_contains($page['type'], 'html')) {
                throw new \RuntimeException('Not a web page');
            }

            return self::parse($page['body'], $page['url']);
        } catch (\Throwable $e) {
            Cache::put('meta-fail:'.$host, 1, self::FAIL_SECONDS);

            throw new \RuntimeException('The shop did not answer in time, or does not allow this.');
        }
    }

    /** @return array{title:?string, description:?string, image:?string, price:?float, currency:?string} */
    public static function parse(string $html, string $baseUrl = ''): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.mb_substr($html, 0, 400_000), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($previous);
        $meta = [];
        foreach ($doc->getElementsByTagName('meta') as $m) {
            $key = strtolower($m->getAttribute('property') ?: $m->getAttribute('name') ?: $m->getAttribute('itemprop'));
            if ($key !== '' && $m->getAttribute('content') !== '' && ! isset($meta[$key])) {
                $meta[$key] = $m->getAttribute('content');
            }
        }
        $ld = self::jsonLdProduct($doc);
        $title = $meta['og:title'] ?? $ld['name'] ?? $meta['twitter:title'] ?? ($doc->getElementsByTagName('title')->item(0)?->textContent);
        $description = $meta['og:description'] ?? $ld['description'] ?? $meta['description'] ?? $meta['twitter:description'] ?? null;
        $image = $meta['og:image'] ?? $meta['twitter:image'] ?? (is_array($ld['image'] ?? null) ? ($ld['image'][0] ?? null) : ($ld['image'] ?? null));
        $price = $meta['product:price:amount'] ?? $meta['og:price:amount'] ?? $ld['offers']['price'] ?? $ld['offers'][0]['price'] ?? $meta['price'] ?? null;
        $currency = $meta['product:price:currency'] ?? $meta['og:price:currency'] ?? $ld['offers']['priceCurrency'] ?? $ld['offers'][0]['priceCurrency'] ?? $meta['pricecurrency'] ?? null;

        return [
            'title' => self::text($title, 100),
            'description' => self::text($description, 500),
            'image' => $image && ($abs = self::absoluteHttps(trim((string) $image), $baseUrl)) ? $abs : null,
            'price' => self::number($price),
            'currency' => $currency && preg_match('/^[A-Za-z]{3}$/', trim((string) $currency)) ? strtoupper(trim((string) $currency)) : null,
        ];
    }

    /** Downloads the picture into our own storage (shrunk), so the page never hot-links a shop. Null when it cannot. */
    public static function storeImage(string $imageUrl): ?string
    {
        try {
            $img = SafeFetch::get($imageUrl, 5_000_000, 2.5, 'image/*');
            if (! str_starts_with($img['type'], 'image/') || str_contains($img['type'], 'svg')) {
                return null;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'pm');
            file_put_contents($tmp, $img['body']);

            return ImageOptimizer::store(new UploadedFile($tmp, 'product', $img['type'], null, true), 'bio-thumbnails', 480);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function jsonLdProduct(\DOMDocument $doc): array
    {
        foreach ($doc->getElementsByTagName('script') as $s) {
            if (strtolower($s->getAttribute('type')) !== 'application/ld+json') {
                continue;
            }
            $data = json_decode($s->textContent, true);
            $nodes = isset($data['@graph']) ? $data['@graph'] : (array_is_list((array) $data) ? (array) $data : [$data]);
            foreach ($nodes as $n) {
                if (is_array($n) && in_array('Product', (array) ($n['@type'] ?? []), true)) {
                    return $n;
                }
            }
        }

        return [];
    }

    private static function text(mixed $v, int $max): ?string
    {
        $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $v))) ?? '');

        return $t === '' ? null : Str::limit($t, $max, '');
    }

    private static function absoluteHttps(string $url, string $base): ?string
    {
        if ($url === '') {
            return null;
        }
        $abs = preg_match('#^https?://#i', $url) ? $url : ($base !== '' ? SafeFetch::absolute($base, $url) : '');

        return preg_match('#^https://#i', $abs) && strlen($abs) <= 500 ? $abs : null;
    }

    /** "120000", "120.000", "1,234.50", "120,000.00" → float; null when it is not a price. */
    private static function number(mixed $v): ?float
    {
        $s = preg_replace('/[^0-9.,]/', '', (string) $v);
        if ($s === '' || ! preg_match('/\d/', $s)) {
            return null;
        }
        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        $sep = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);
        if ($sep >= 0 && strlen($s) - $sep - 1 === 3 && substr_count($s, $s[$sep]) >= 1 && ! ($lastDot !== false && $lastComma !== false)) {
            $s = str_replace(['.', ','], '', $s);   // 120.000 or 1,234: thousands
        } elseif ($sep >= 0) {
            $s = str_replace(['.', ','], '', substr($s, 0, $sep)).'.'.substr($s, $sep + 1);
        }
        $n = (float) $s;

        return $n >= 0 && $n < 1_000_000_000 ? $n : null;
    }
}
