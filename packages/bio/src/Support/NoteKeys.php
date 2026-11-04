<?php

namespace Hop\Bio\Support;

use Hop\Safety\EncryptedNoteLink;
use Illuminate\Validation\ValidationException;

/**
 * Finds the key of an encrypted NoteHop note (see EncryptedNoteLink) in anything a page or a block stores, not only in
 * the fields that hold an address. A block's text is rendered as HTML, so an entity is read as the character it
 * stands for and an attribute the way a browser reads it. A note address without a key, and the same fragment on
 * another host, are ordinary content.
 */
class NoteKeys
{
    /** What ends an address inside a longer text. */
    private const TOKEN = '[^\s"\'<>()\[\]{}]';

    /** The # that starts a fragment, as text or as an entity (once or more escaped). */
    private const HASH = '(?:#|%23|&(?:amp;)*(?:\#0*35;?|\#x0*23;?|num;))';

    /** Refuses the save when any value holds a key, and keeps the key out of the input flashed back to the form. */
    public static function assertNone(array $data): void
    {
        $paths = self::paths($data);
        if ($paths === []) {
            return;
        }
        request()->merge(self::scrubArray(request()->input()));

        throw ValidationException::withMessages(array_fill_keys($paths, (new EncryptedNoteLink)->message()));
    }

    /** @return list<string> Dotted paths of the values that hold a key (a key in an array's own key names its parent). */
    public static function paths(array $data, string $prefix = ''): array
    {
        $paths = [];
        foreach ($data as $name => $value) {
            if (is_string($name) && self::holds($name)) {
                $paths[] = rtrim($prefix, '.');
            } elseif (is_array($value)) {
                $paths = [...$paths, ...self::paths($value, $prefix.$name.'.')];
            } elseif (is_string($value) && self::holds($value)) {
                $paths[] = $prefix.$name;
            }
        }

        return $paths;
    }

    public static function holds(string $text): bool
    {
        $data = json_decode($text, true);
        if (is_array($data)) {
            return self::paths($data) !== [];
        }
        $notes = new EncryptedNoteLink;
        $text = self::decoded($text);
        if ($notes->containsKey($text)) {
            return true;
        }
        foreach (self::quoted($text) as $value) {
            if ($notes->matches($value)) {
                return true;
            }
        }
        preg_match_all('~'.self::TOKEN.'+~', self::joined($text), $tokens);
        foreach ($tokens[0] as $token) {
            if (self::tokenHolds($notes, $token)) {
                return true;
            }
        }

        return false;
    }

    /** The value without the keys in it; everything else stays as it was. A JSON value is only rewritten when it changed. */
    public static function scrub(string $value): string
    {
        if (! self::holds($value)) {
            return $value;
        }
        $data = json_decode($value, true);
        if (is_array($data)) {
            return json_encode(self::scrubArray($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $value;
        }
        $notes = new EncryptedNoteLink;
        $clean = preg_replace_callback('~"([^"]*)"|\'([^\']*)\'~', function (array $m) use ($notes) {
            $inner = $m[2] ?? $m[1];

            return $notes->matches(self::decoded($inner)) ? str_replace($inner, self::withoutKey($notes, $inner), $m[0]) : $m[0];
        }, $value) ?? $value;

        return preg_replace_callback('~'.self::TOKEN.'*'.self::HASH.'[\t\r\n]*(?:'.self::TOKEN.'|\t)*|'.self::TOKEN.'+~i', function (array $m) use ($notes) {
            return self::tokenHolds($notes, self::joined(self::decoded($m[0]))) ? self::withoutKey($notes, $m[0]) : $m[0];
        }, $clean) ?? $clean;
    }

    public static function scrubArray(array $data): array
    {
        $clean = [];
        foreach ($data as $name => $value) {
            $clean[is_string($name) ? self::scrub($name) : $name] = match (true) {
                is_array($value) => self::scrubArray($value),
                is_string($value) => self::scrub($value),
                default => $value,
            };
        }

        return $clean;
    }

    /** One run of text: an address on its own, or glued to a label ("note:note.hel.ink/x#k=..."). */
    private static function tokenHolds(EncryptedNoteLink $notes, string $token): bool
    {
        if ($notes->containsKey($token)) {
            return true;
        }
        $head = preg_split('/#|%23/i', $token, 2);
        if (count($head) < 2) {
            return false;
        }
        preg_match_all('/[:=,;|]/', $head[0], $glue, PREG_OFFSET_CAPTURE);
        foreach ($glue[0] as [, $at]) {
            if ($notes->matches(substr($token, $at + 1))) {
                return true;
            }
        }

        return false;
    }

    private static function withoutKey(EncryptedNoteLink $notes, string $address): string
    {
        return $notes->matches($address) && ! preg_match('/[\t\r\n]/', $address)
            ? EncryptedNoteLink::withoutSecrets($address)
            : preg_split('~'.self::HASH.'~i', $address, 2)[0];
    }

    /** The text with entities read as characters, however many times they were escaped. */
    private static function decoded(string $text): string
    {
        for ($round = 0; $round < 4; $round++) {
            $next = preg_replace(['/&#0*35(?![0-9;])/', '/&#x0*23(?![0-9a-f;])/i'], '#', $text) ?? $text;
            $next = html_entity_decode($next, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $text) {
                break;
            }
            $text = $next;
        }

        return $text;
    }

    /** A tab, and a line break right after the #, do not end an address for someone who copies it. */
    private static function joined(string $text): string
    {
        return preg_replace(['/\t/', '/#[\r\n]+/'], ['', '#'], $text) ?? $text;
    }

    /** @return list<string> Quoted values (HTML attributes, CSS url()). A browser drops tabs and line breaks in them. */
    private static function quoted(string $text): array
    {
        preg_match_all('~"([^"]*)"|\'([^\']*)\'~', $text, $m);

        return array_values(array_filter([...$m[1], ...$m[2]], fn ($value) => $value !== ''));
    }
}
