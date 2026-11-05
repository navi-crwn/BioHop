<?php

namespace Hop\Bio\Exceptions;

/** PixelHop could not list the member's files. `reason` is `no_account` (never signed in there) or `unavailable`. */
class PixelHopUnavailable extends \RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'unavailable', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
