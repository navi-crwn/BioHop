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
        if (! Schema::hasTable('bio_links')) {
            return; // nothing to alter yet (fresh database runs the create migrations first)
        }
        Schema::table('bio_links', function (Blueprint $table) {
            $table->string('title', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bio_links', function (Blueprint $table) {
            $table->string('title', 100)->nullable(false)->default('')->change();
        });
    }
};
