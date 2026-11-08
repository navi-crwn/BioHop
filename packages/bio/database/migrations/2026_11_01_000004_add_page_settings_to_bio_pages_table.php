<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sharing picture and favicon, Google Tag Manager, a fallback address, a footer line, and privacy mode.
     * Each column is added only when it is missing: on a shared database LinkHop's bio package may have
     * added `favicon` already (its 2026_11_10 migration), and a fresh family install runs LinkHop first.
     */
    private const COLUMNS = ['og_image', 'favicon', 'gtm_id', 'fallback_url', 'footer_note', 'footer_url', 'privacy_mode'];

    public function up(): void
    {
        if (! Schema::hasTable('bio_pages')) {
            return;
        }
        $missing = array_values(array_filter(self::COLUMNS, fn (string $c) => ! Schema::hasColumn('bio_pages', $c)));
        if ($missing === []) {
            return;
        }
        Schema::table('bio_pages', function (Blueprint $table) use ($missing) {
            $add = array_flip($missing);
            isset($add['og_image']) && $table->string('og_image', 500)->nullable()->after('seo_description');
            isset($add['favicon']) && $table->string('favicon', 500)->nullable()->after('og_image');
            isset($add['gtm_id']) && $table->string('gtm_id', 30)->nullable()->after('tiktok_pixel_id');
            isset($add['fallback_url']) && $table->string('fallback_url', 500)->nullable()->after('is_published');
            isset($add['footer_note']) && $table->string('footer_note', 200)->nullable()->after('hide_branding');
            isset($add['footer_url']) && $table->string('footer_url', 500)->nullable()->after('footer_note');
            isset($add['privacy_mode']) && $table->boolean('privacy_mode')->default(false)->after('footer_url');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'og_image')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn(['og_image', 'favicon', 'gtm_id', 'fallback_url', 'footer_note', 'footer_url', 'privacy_mode']));
        }
    }
};
