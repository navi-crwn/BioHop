<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A block can go live and expire on its own; a page can add UTM parameters to every outgoing link. */
    public function up(): void
    {
        if (Schema::hasTable('bio_links') && ! Schema::hasColumn('bio_links', 'publish_at')) {
            Schema::table('bio_links', function (Blueprint $table) {
                $table->timestamp('publish_at')->nullable()->after('archived_at');
                $table->timestamp('expire_at')->nullable()->after('publish_at');
            });
        }
        if (Schema::hasTable('bio_pages') && ! Schema::hasColumn('bio_pages', 'auto_utm')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->boolean('auto_utm')->default(false)->after('hide_branding'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_links') && Schema::hasColumn('bio_links', 'publish_at')) {
            Schema::table('bio_links', fn (Blueprint $table) => $table->dropColumn(['publish_at', 'expire_at']));
        }
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'auto_utm')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn('auto_utm'));
        }
    }
};
