<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A public abuse report. The table is shared with LinkHop (helink_db), so the
 * HOP control panel sees reports from both apps. `source` says which app a report
 * is about (biohop for this app's form); LinkHop's migration adds the column.
 */
class AbuseReport extends Model
{
    protected $fillable = ['slug', 'url', 'email', 'reason', 'ip_address', 'status', 'source'];
}
