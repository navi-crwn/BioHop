<?php

namespace Hop\Bio\Exceptions;

/**
 * Thrown by a ShortlinkCreator when the shortener cannot be used right now
 * (down, unreachable, refused). The editor keeps the block's original URL and
 * tells the member; it is not a bug to report.
 */
class ShortlinkUnavailable extends \RuntimeException {}
