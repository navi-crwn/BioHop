<?php

namespace Hop\Bio\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * The social icons of a page, stored as a JSON list. Reading drops anything that is not an icon (a null from data
 * saved before the check on save, a stray string), so one bad entry cannot stop the editor from opening.
 */
class SocialLinksCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        $list = json_decode((string) $value, true);

        return is_array($list) ? array_values(array_filter($list, 'is_array')) : [];
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value === null ? null : json_encode($value);
    }
}
