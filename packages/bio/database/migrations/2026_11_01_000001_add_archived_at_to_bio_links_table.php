<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** An archived block leaves the page and the editor's list but keeps its clicks; it can be restored. */
    public function up(): void
    {
        if (! Schema::hasTable('bio_links') || Schema::hasColumn('bio_links', 'archived_at')) {
            return;
        }
        Schema::table('bio_links', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_active')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bio_links') && Schema::hasColumn('bio_links', 'archived_at')) {
            Schema::table('bio_links', fn (Blueprint $table) => $table->dropColumn('archived_at'));
        }
    }
};
