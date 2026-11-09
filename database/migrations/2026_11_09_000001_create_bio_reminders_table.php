<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** BioHop's own table: when a member hid a reminder card (see App\Support\Reminders). One row per account and card. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bio_reminders')) {
            return;
        }
        Schema::create('bio_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('key', 64);
            $table->timestamp('dismissed_at');
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_reminders');
    }
};
