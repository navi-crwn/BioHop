<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New bio pages are not offered to search engines until their owner switches indexing on (opt-in). This only changes
 * the default of the column, which applies to rows created from now on: every existing row keeps the value it has
 * (pages made while the default was "yes" stay indexed). Nothing is backfilled.
 *
 * On SQLite the column is left alone: SQLite can only change a default by rebuilding the table, and the rebuild
 * deletes the page's blocks through the cascading foreign key. There the default comes from the model (BioPage), which
 * is what every create path goes through.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setDefault(false);
    }

    public function down(): void
    {
        $this->setDefault(true);
    }

    private function setDefault(bool $value): void
    {
        if (! Schema::hasTable('bio_pages') || ! Schema::hasColumn('bio_pages', 'allow_indexing') || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('bio_pages', function (Blueprint $table) use ($value) {
            $table->boolean('allow_indexing')->default($value)->change();
        });
    }
};
