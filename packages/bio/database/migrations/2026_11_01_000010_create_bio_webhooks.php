<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A page can send each new form entry to an address of the owner's, signed; every try is logged so it can be sent again. */
    public function up(): void
    {
        if (Schema::hasTable('bio_pages') && ! Schema::hasColumn('bio_pages', 'webhook_url')) {
            Schema::table('bio_pages', function (Blueprint $table) {
                $table->string('webhook_url', 500)->nullable()->after('footer_url');
                $table->text('webhook_secret')->nullable()->after('webhook_url');
            });
        }
        if (! Schema::hasTable('bio_webhook_deliveries') && Schema::hasTable('bio_pages')) {
            Schema::create('bio_webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bio_page_id')->constrained('bio_pages')->cascadeOnDelete();
                $table->string('uuid', 36)->unique();
                $table->string('event', 30);
                $table->longText('payload');
                $table->string('status', 10)->default('pending'); // pending, sent, failed
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedSmallInteger('http_code')->nullable();
                $table->string('error', 200)->nullable();
                $table->timestamp('next_attempt_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_webhook_deliveries');
        if (Schema::hasTable('bio_pages') && Schema::hasColumn('bio_pages', 'webhook_url')) {
            Schema::table('bio_pages', fn (Blueprint $table) => $table->dropColumn(['webhook_url', 'webhook_secret']));
        }
    }
};
