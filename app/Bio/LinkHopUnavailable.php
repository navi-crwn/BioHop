<?php

namespace App\Bio;

use Hop\Bio\Exceptions\ShortlinkUnavailable;

/** LinkHop (the shortener) could not make or list a link: off, down, or an error reply. */
class LinkHopUnavailable extends ShortlinkUnavailable {}
