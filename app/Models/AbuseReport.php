<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A public abuse report. The table is shared with LinkHop (helink_db), so the
 * HOP control panel sees reports from both apps in one queue.
 */
class AbuseReport extends Model
{
    protected $fillable = ['slug', 'url', 'email', 'reason', 'ip_address', 'status'];
}
