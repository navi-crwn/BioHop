<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Saved versions of a page: its design and content and its blocks, so an earlier state can be brought back. */
    public function up(): void
    {
        if (Schema::hasTable('bio_revisions') || ! Schema::hasTable('bio_pages')) {
            return;
        }
        Schema::create('bio_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->constrained('bio_pages')->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->unsignedSmallInteger('blocks_count')->default(0);
            $table->longText('snapshot');
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_revisions');
    }
};
