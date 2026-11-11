<?php

namespace Hop\Bio\Support;

/**
 * The "receive payment" block. The list of payment formats is resources/pay/formats.json, which
 * scripts/vendor-hop-pay.mjs builds from the vendored hop-pay module's own format list; nothing here names a format.
 *
 * Stored as settings.pay = { format, fields: {key: value}, amount, note, open_amount }. The payment details are the
 * page owner's own data, kept like any block setting and escaped on output. The QR payload is built in the visitor's
 * browser by hop-pay (resources/js/pay-shared.js); the server checks only the shape (format, field names, lengths,
 * control characters, required fields) and prints the details as text, so the page reads without scripts.
 */
class PayBlock
{
    /** @var array<string, array>|null */
    private static ?array $formats = null;

    /** @return array<string, array> format id => {name, group, confidence, country, currencies, payee, note, fields[]} */
    public static function formats(): array
    {
        return self::$formats ??= (array) json_decode((string) file_get_contents(resource_path('pay/formats.json')), true);
    }

    /** Messages the editor and the public page show for an error code of hop-pay (or "required", "other"). */
    public static function messages(): array
    {
        $generic = __('This does not look like a valid name, number or address for this method.');

        return [
            'required' => __('Fill in this field.'),
            'receiver_name_required' => __('Enter the payee name.'),
            'too_long' => __('This is too long.'),
            'hidden_characters' => __('The name contains hidden characters. Retype it.'),
            'bad_handle' => $generic, 'bad_address' => $generic, 'bad_account' => $generic, 'bad_bin' => $generic,
            'bad_bic' => $generic, 'bad_name' => $generic, 'bad_purpose' => $generic,
            'bad_iban' => __('This IBAN is not valid. Check the digits.'),
            'amount_format' => __('Enter the amount as digits, with at most one decimal point.'),
            'amount_separator' => __('Leave out thousands separators: write 1000.50, not 1,000.50.'),
            'amount_no_decimals' => __('This currency has no decimals.'),
            'amount_not_positive' => __('The amount must be more than zero.'),
            'amount_too_large' => __('This amount is too large.'),
            'bad_currency' => __('Choose a currency this method supports.'),
            'currency_required' => __('Choose a currency this method supports.'),
            'currency_unsupported' => __('Choose a currency this method supports.'),
            'unknown_format' => __('Choose a payment method.'),
            'other' => __('A code cannot be made from these details. Check the fields.'),
        ];
    }

    /** English label/hint => translated, for the editor form (the English strings are the language-file keys). */
    public static function labels(): array
    {
        $out = [];
        foreach (self::formats() as $def) {
            foreach ($def['fields'] as $f) {
                foreach (['label', 'hint'] as $k) {
                    if (isset($f[$k])) {
                        $out[$f[$k]] = __($f[$k]);
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Checks the shape of a stored block and returns [cleaned value, errors by dotted field name].
     * Strict module validation (IBAN checksums, amount rules, hidden characters) runs in the browser, in the editor
     * before saving and again before a code is drawn; a value that passes here but not there shows its text only.
     *
     * @return array{0: ?array, 1: array<string, string>}
     */
    public static function clean(mixed $pay): array
    {
        $formats = self::formats();
        if (! is_array($pay) || ! isset($formats[$pay['format'] ?? '']) || ! is_string($pay['format'])) {
            return [null, ['settings.pay.format' => __('Choose a payment method.')]];
        }
        $id = $pay['format'];
        $def = $formats[$id];
        $errors = [];
        $in = is_array($pay['fields'] ?? null) ? $pay['fields'] : [];
        $fields = [];
        foreach ($def['fields'] as $f) {
            $v = $in[$f['key']] ?? '';
            if (! is_scalar($v)) {
                $v = '';
            }
            $v = trim((string) $v);
            $name = 'settings.pay.fields.'.$f['key'];
            if (($f['kind'] ?? '') === 'currency') {
                $v = strtoupper($v) !== '' ? strtoupper($v) : (string) ($f['default'] ?? '');
                if (! in_array($v, $def['currencies'], true)) {
                    $errors[$name] = self::messages()['bad_currency'];
                }
            } elseif ($v === '' && empty($f['optional'])) {
                $errors[$name] = self::messages()['required'];
            } elseif (mb_strlen($v) > (int) $f['max']) {
                $errors[$name] = self::messages()['too_long'];
            } elseif (self::hasControl($v)) {
                $errors[$name] = self::messages()['hidden_characters'];
            }
            if ($v !== '') {
                $fields[$f['key']] = $v;
            }
        }
        $amount = is_scalar($pay['amount'] ?? null) ? trim((string) $pay['amount']) : '';
        if ($amount !== '' && ! preg_match('/^[0-9]{1,13}([.,][0-9]{1,9})?$/', $amount)) {
            $errors['settings.pay.amount'] = self::messages()['amount_format'];
        }
        $note = is_scalar($pay['note'] ?? null) ? trim((string) $pay['note']) : '';
        if (mb_strlen($note) > 140) {
            $errors['settings.pay.note'] = self::messages()['too_long'];
        } elseif (self::hasControl($note)) {
            $errors['settings.pay.note'] = self::messages()['hidden_characters'];
        }
        // a fixed amount and "let visitors type it" exclude each other
        $open = filter_var($pay['open_amount'] ?? false, FILTER_VALIDATE_BOOLEAN) && $amount === '';

        return [['format' => $id, 'fields' => $fields, 'amount' => $amount, 'note' => $def['note'] ? $note : '', 'open_amount' => $open], $errors];
    }

    /** The texts of a block that a content check should read: the payee name and the note. */
    public static function text(mixed $pay): string
    {
        $def = is_array($pay) && is_string($pay['format'] ?? null) ? (self::formats()[$pay['format']] ?? null) : null;
        if (! $def) {
            return '';
        }
        $name = $pay['fields'][$def['payee']] ?? '';

        return trim((is_scalar($name) ? (string) $name : '').' '.(is_scalar($pay['note'] ?? null) ? (string) $pay['note'] : ''));
    }

    /** Control, format (zero-width, bidi override) and line-break characters never belong in a payee name or note. */
    private static function hasControl(string $v): bool
    {
        return (bool) preg_match('/[\p{Cc}\p{Cf}]/u', $v);
    }

    /**
     * What the public page prints. Null when the block holds nothing usable (unknown format, a missing required field),
     * so it is simply not shown.
     *
     * @return array{format:string, scheme:string, payee:string, rows:array, amount:string, currency:string, note:string, open:bool, pay:array}|null
     */
    public static function view(?array $pay): ?array
    {
        $def = self::formats()[$pay['format'] ?? ''] ?? null;
        if (! $def) {
            return null;
        }
        [$clean, $errors] = self::clean($pay);
        if (! $clean || $errors !== []) {
            return null;
        }
        $rows = [];
        foreach ($def['fields'] as $f) {
            if (($clean['fields'][$f['key']] ?? '') !== '' && $f['key'] !== $def['payee']) {
                $rows[] = ['label' => __($f['label']), 'value' => $clean['fields'][$f['key']], 'mono' => ! empty($f['mono'])];
            }
        }
        $currency = $clean['fields']['currency'] ?? $def['currencies'][0];
        if ($clean['format'] === 'solana' && ($clean['fields']['splToken'] ?? '') !== '') {
            $currency = 'SPL';
        }

        return [
            'format' => $clean['format'],
            'scheme' => $def['name'],
            'payee' => $clean['fields'][$def['payee']] ?? '',
            'rows' => $rows,
            'amount' => $clean['amount'],
            'currency' => $currency,
            'note' => $clean['note'],
            'open' => $clean['open_amount'],
            'pay' => $clean,
        ];
    }
}
