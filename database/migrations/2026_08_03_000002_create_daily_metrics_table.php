<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instance_id');
            $table->date('date');
            $table->string('metric_name');
            $table->bigInteger('metric_value')->default(0);
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->foreign('instance_id')
                  ->references('id')
                  ->on('instances')
                  ->onDelete('cascade');

            $table->unique(['instance_id', 'date', 'metric_name']);
            $table->index(['date', 'metric_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
