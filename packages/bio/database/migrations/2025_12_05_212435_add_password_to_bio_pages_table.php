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
        if (! Schema::hasTable('bio_pages') || Schema::hasColumn('bio_pages', 'password_enabled')) {
            return; // already applied by LinkHop (shared database)
        }
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->boolean('password_enabled')->default(false)->after('is_published');
            $table->string('password', 255)->nullable()->after('password_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn(['password_enabled', 'password']);
        });
    }
};
