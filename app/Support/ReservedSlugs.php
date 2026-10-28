<?php

namespace App\Support;

/**
 * Words that cannot be a bio slug: the path prefixes the router hands to the
 * apps (hel.ink/l/, /b/, /api/) and `bio.reserved_slugs` (staff-looking names,
 * brands, hub paths). Matching ignores case.
 */
class ReservedSlugs
{
    /** Always reserved, whatever the config says. */
    public const PREFIXES = ['l', 'b', 'api'];

    public static function isReserved(?string $slug): bool
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '') {
            return false;
        }

        return in_array($slug, self::PREFIXES, true)
            || in_array($slug, array_map('strtolower', (array) config('bio.reserved_slugs', [])), true);
    }

    /** Validation rule; $current is the slug the record already has, which stays allowed. */
    public static function rule(?string $current = null, string $message = 'This slug is reserved.'): \Closure
    {
        return function ($attribute, $value, $fail) use ($current, $message) {
            if (is_string($value) && strcasecmp($value, (string) $current) !== 0 && self::isReserved($value)) {
                $fail($message);
            }
        };
    }
}
