<?php

namespace Hop\Bio\Exceptions;

/** NoteHop could not list the member's notes. `reason` is `no_account` (never signed in there) or `unavailable`. */
class NoteHopUnavailable extends \RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'unavailable', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
