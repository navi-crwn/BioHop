<?php

namespace Hop\Bio\Support;

use Hop\Auth\Ticket;

/**
 * The secret that signs the editor's picker calls to the other HOP apps (PixelHop's /api/v1/me/media, NoteHop's
 * /api/v1/me/notes). HOP_PICKER_SECRET, when set (32+ characters), is a secret of its own that the hub never uses:
 * the apps that verify picker calls hold it instead of this app's SSO secret, so a leak there cannot be turned into
 * a forged sign-in here. Without it the SSO secret signs, as before.
 */
class PickerSecret
{
    public static function signing(): ?string
    {
        $own = (string) config('bio.picker_secret');
        if (strlen($own) >= 32) {
            return $own;
        }

        return Ticket::resolveSecrets()[0] ?? null;
    }

    /** Whether the picker calls use their own secret rather than the SSO secret. */
    public static function dedicated(): bool
    {
        return strlen((string) config('bio.picker_secret')) >= 32;
    }
}
