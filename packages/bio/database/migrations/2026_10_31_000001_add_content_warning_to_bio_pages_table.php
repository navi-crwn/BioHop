<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages')) {
            return;
        }

        Schema::table('bio_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('bio_pages', 'content_warning')) {
                $table->string('content_warning', 32)->default('none')->after('is_adult_content');
            }
            if (! Schema::hasColumn('bio_pages', 'warning_reason')) {
                $table->string('warning_reason', 255)->nullable()->after('content_warning');
            }
        });

        // Migrate existing adult pages: if is_adult_content is true, set content_warning to 'adult'
        if (Schema::hasColumn('bio_pages', 'is_adult_content')) {
            DB::table('bio_pages')->where('is_adult_content', true)->update(['content_warning' => 'adult']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            if (Schema::hasColumn('bio_pages', 'warning_reason')) {
                $table->dropColumn('warning_reason');
            }
            if (Schema::hasColumn('bio_pages', 'content_warning')) {
                $table->dropColumn('content_warning');
            }
        });
    }
};

