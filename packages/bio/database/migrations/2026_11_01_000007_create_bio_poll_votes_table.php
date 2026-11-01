<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** One vote per visitor per poll block; the visitor is a keyed hash of network address and browser, never the address. */
    public function up(): void
    {
        if (Schema::hasTable('bio_poll_votes') || ! Schema::hasTable('bio_links')) {
            return;
        }
        Schema::create('bio_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_link_id')->constrained('bio_links')->cascadeOnDelete();
            $table->unsignedTinyInteger('option_index');
            $table->string('voter_hash', 45);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['bio_link_id', 'voter_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_poll_votes');
    }
};
