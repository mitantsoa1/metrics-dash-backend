<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_report_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scheduled_report_id');
            $table->timestamp('executed_at');
            $table->string('status'); // SUCCESS, FAILED
            $table->unsignedInteger('recipients_count')->default(0);
            $table->json('recipients_sent')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();

            $table->foreign('scheduled_report_id')
                  ->references('id')
                  ->on('scheduled_reports')
                  ->onDelete('cascade');

            $table->index(['scheduled_report_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_report_logs');
    }
};
