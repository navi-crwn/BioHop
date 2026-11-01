<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** How the visitor reached the page: a printed QR code (qr), an NFC tag (nfc) or the home-screen app (pwa); empty means a plain link. */
    public function up(): void
    {
        if (! Schema::hasTable('bio_clicks') || Schema::hasColumn('bio_clicks', 'source')) {
            return;
        }
        Schema::table('bio_clicks', fn (Blueprint $table) => $table->string('source', 10)->nullable()->after('referrer'));
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_clicks') && Schema::hasColumn('bio_clicks', 'source')) {
            Schema::table('bio_clicks', fn (Blueprint $table) => $table->dropColumn('source'));
        }
    }
};
