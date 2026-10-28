<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages') || Schema::hasColumn('bio_pages', 'qr_settings')) {
            return; // already applied by LinkHop (shared database)
        }
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->json('qr_settings')->nullable()->after('allow_indexing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn('qr_settings');
        });
    }
};
