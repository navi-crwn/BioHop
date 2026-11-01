<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Sharing picture and favicon, Google Tag Manager, a fallback address, a footer line, and privacy mode. */
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages') || Schema::hasColumn('bio_pages', 'og_image')) {
            return;
        }
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('og_image', 500)->nullable()->after('seo_description');
            $table->string('favicon', 500)->nullable()->after('og_image');
            $table->string('gtm_id', 30)->nullable()->after('tiktok_pixel_id');
            $table->string('fallback_url', 500)->nullable()->after('is_published');
            $table->string('footer_note', 200)->nullable()->after('hide_branding');
            $table->string('footer_url', 500)->nullable()->after('footer_note');
            $table->boolean('privacy_mode')->default(false)->after('footer_url');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'og_image')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn(['og_image', 'favicon', 'gtm_id', 'fallback_url', 'footer_note', 'footer_url', 'privacy_mode']));
        }
    }
};
