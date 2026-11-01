<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Entries sent through a page's form block. Kept for the days the owner chose, then pruned. */
    public function up(): void
    {
        if (Schema::hasTable('bio_leads') || ! Schema::hasTable('bio_links')) {
            return;
        }
        Schema::create('bio_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_link_id')->constrained('bio_links')->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('ip_hash', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_leads');
    }
};
