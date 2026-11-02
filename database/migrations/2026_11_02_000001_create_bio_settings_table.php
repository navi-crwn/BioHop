<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** BioHop's own table: limits an operator changes from the admin panel (see App\Support\BioLimits). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bio_settings')) {
            return;
        }
        Schema::create('bio_settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_settings');
    }
};
