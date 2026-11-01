<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** How the top of the page is laid out (classic, hero, banner, cutout) and the banner's cover picture. */
    public function up(): void
    {
        if (! Schema::hasTable('bio_pages') || Schema::hasColumn('bio_pages', 'header_layout')) {
            return;
        }
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('header_layout', 20)->default('classic')->after('layout');
            $table->string('cover_image', 500)->nullable()->after('header_layout');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'header_layout')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn(['header_layout', 'cover_image']));
        }
    }
};
