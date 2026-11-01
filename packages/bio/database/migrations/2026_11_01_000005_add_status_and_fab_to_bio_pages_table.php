<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A status chip under the name (with an optional end) and a floating button in the corner of the page. */
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages') || Schema::hasColumn('bio_pages', 'status_text')) {
            return;
        }
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('status_text', 60)->nullable()->after('bio');
            $table->timestamp('status_until')->nullable()->after('status_text');
            $table->string('fab_type', 20)->default('none')->after('privacy_mode');
            $table->string('fab_label', 30)->nullable()->after('fab_type');
            $table->string('fab_url', 500)->nullable()->after('fab_label');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'status_text')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn(['status_text', 'status_until', 'fab_type', 'fab_label', 'fab_url']));
        }
    }
};
