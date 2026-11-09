<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('name', 100);
            $table->string('email', 150);
            $table->string('role', 80)->default('Commercial / Account Manager');
            $table->string('subject', 200);
            $table->text('message');
            $table->string('status', 30)->default('open'); // open, in_progress, resolved, closed
            $table->string('priority', 30)->default('normal'); // low, normal, high, urgent
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('set null');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['email']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
