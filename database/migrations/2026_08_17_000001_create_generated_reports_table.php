<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('instance_id')->nullable();
            $table->string('name');
            $table->string('format'); // PDF, EXCEL, CSV
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('file_path')->nullable();
            $table->longText('file_data')->nullable(); // Base64 or cached payload for instant re-download
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('records_count')->default(0);
            $table->json('kpi_summary')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('instance_id')
                  ->references('id')
                  ->on('instances')
                  ->onDelete('set null');

            $table->index(['tenant_id', 'created_at']);
            $table->index(['instance_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
