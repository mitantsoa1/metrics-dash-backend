<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('token_hash', 64)->unique(); // SHA-256 hash
            $table->string('device_name', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('replaced_by_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_id');
            $table->index(['user_id', 'revoked_at']);
            $table->index('expires_at');
        });

        // Add self-referencing foreign key after table creation for PostgreSQL compatibility
        Schema::table('user_refresh_tokens', function (Blueprint $table) {
            $table->foreign('replaced_by_id')
                ->references('id')
                ->on('user_refresh_tokens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_refresh_tokens');
    }
};
