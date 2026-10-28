<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TESTS ONLY (loaded by AppServiceProvider when running the test suite). The
 * tables other apps own in the shared database: users, roles, api_tokens and
 * Laravel's own (hub), ip_bans, domain_blacklist and abuse_flags (LinkHop).
 * Columns follow the hub's 0001_01_01_000000_hub_account_tables migration and
 * LinkHop's migrations; keep them in step when those change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('google_id')->nullable()->unique();
            $table->string('username', 30)->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('bio', 300)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('location', 100)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('show_owner')->default(true);
            $table->unsignedInteger('profile_version')->default(1);
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->string('last_login_ip', 45)->nullable();
            $table->string('last_login_country', 2)->nullable();
            $table->string('last_login_city')->nullable();
            $table->string('last_login_provider')->nullable();
            $table->string('last_login_device')->nullable();
            $table->string('last_login_browser')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->integer('rate_limit')->default(100);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->json('scopes')->nullable();
            $table->index(['user_id', 'token']);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->string('app_slug', 32)->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role', 'app_slug']);
        });
        // LinkHop's tables that BioHop reads (ip_bans, domain_blacklist) or writes findings to (abuse_flags).
        Schema::create('ip_bans', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('domain_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('match_type', 16)->default('exact');
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('domain');
            $table->index('category');
        });
        Schema::create('abuse_flags', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->json('signals')->nullable();
            $table->string('action', 20)->default('flagged');
            $table->string('status', 20)->default('open');
            $table->string('summary', 255)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['abuse_flags', 'domain_blacklist', 'ip_bans', 'roles', 'api_tokens', 'failed_jobs', 'jobs', 'cache_locks', 'cache', 'sessions', 'users'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
