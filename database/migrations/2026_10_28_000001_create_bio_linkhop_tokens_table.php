<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** BioHop's own table: the member's service token for LinkHop's API (see App\Bio\LinkHopShortlinks). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bio_linkhop_tokens')) {
            return;
        }
        Schema::create('bio_linkhop_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('api_token_id')->nullable();
            $table->text('token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_linkhop_tokens');
    }
};
