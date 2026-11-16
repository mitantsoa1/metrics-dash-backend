<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('instance_id')->nullable();
            $table->string('name');
            $table->string('frequency'); // DAILY, WEEKLY, MONTHLY
            $table->unsignedTinyInteger('day_of_week')->nullable()->default(1); // 1 = Monday ... 7 = Sunday
            $table->unsignedTinyInteger('day_of_month')->nullable()->default(1); // 1..31
            $table->string('time')->default('09:00'); // HH:mm
            $table->string('timezone')->default('Europe/Paris');
            $table->string('formats')->default('PDF'); // PDF, EXCEL, BOTH
            $table->json('recipients'); // Array of email strings
            $table->json('included_kpis')->nullable(); // Array of KPI keys: audience, financials, top_pages, top_clicks, device_breakdown
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('instance_id')
                  ->references('id')
                  ->on('instances')
                  ->onDelete('set null');

            $table->index(['tenant_id', 'is_active']);
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
