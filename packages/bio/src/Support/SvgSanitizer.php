<?php

namespace Hop\Bio\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Makes an uploaded SVG safe to serve from our own origin.
 *
 * An SVG is a document: opened directly it can run script, load other files and embed HTML. So the upload is
 * parsed (no network, no entities, no DOCTYPE) and rebuilt from an allowlist of drawing elements; event handlers,
 * external references, scripts and foreign content are dropped. Returns null when the file is not a plain SVG.
 */
final class SvgSanitizer
{
    public const MAX_BYTES = 1048576;

    private const ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'rect', 'text', 'tspan', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'pattern', 'style',
        'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'feflood', 'fecomposite', 'femerge', 'femergenode',
        'fedropshadow', 'metadata',
    ];

    public static function clean(string $svg): ?string
    {
        if ($svg === '' || strlen($svg) > self::MAX_BYTES) {
            return null;
        }
        // A DOCTYPE or an entity is how an SVG reads local files or explodes in size: refuse, don't strip.
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            return null;
        }
        // Drop a byte order mark and anything before the first tag.
        $svg = preg_replace('/^\xEF\xBB\xBF/', '', $svg);
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $loaded ? $dom->documentElement : null;
        if (! $root || strtolower($root->localName) !== 'svg') {
            return null;
        }
        self::cleanAttributes($root);
        self::walk($root);
        if (! $root->hasAttribute('xmlns')) {
            $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        }
        $out = $dom->saveXML($root);

        return $out === false || $out === '' ? null : $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if (! in_array(strtolower($child->localName), self::ELEMENTS, true) || ! self::allowedNamespace($child)) {
                    $node->removeChild($child);

                    continue;
                }
                if (strtolower($child->localName) === 'style' && self::unsafeCss($child->textContent)) {
                    $node->removeChild($child);

                    continue;
                }
                self::cleanAttributes($child);
                self::walk($child);
            } elseif (! in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                // comments, processing instructions
                $node->removeChild($child);
            }
        }
    }

    private static function allowedNamespace(DOMElement $element): bool
    {
        return $element->namespaceURI === null || $element->namespaceURI === 'http://www.w3.org/2000/svg';
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->value);
            $drop = str_starts_with($name, 'on')
                || in_array($name, ['src', 'srcset', 'action', 'formaction'], true)
                || preg_match('/javascript:|vbscript:|data:text\/html|expression\s*\(/i', $value)
                // Links may only point inside the file.
                || (in_array($name, ['href', 'xlink:href'], true) && ! str_starts_with($value, '#'))
                || ($name === 'style' && self::unsafeCss($value))
                || (self::referencesElsewhere($value));
            if ($drop) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    /** url(...) may only point at an id in the same file (gradients, clip paths). */
    private static function referencesElsewhere(string $value): bool
    {
        return (bool) preg_match('/url\(\s*[\'"]?\s*(?!#)/i', $value);
    }

    private static function unsafeCss(string $css): bool
    {
        return (bool) preg_match('/@import|javascript:|expression\s*\(|behaviou?r\s*:|-moz-binding/i', $css)
            || self::referencesElsewhere($css);
    }
}
