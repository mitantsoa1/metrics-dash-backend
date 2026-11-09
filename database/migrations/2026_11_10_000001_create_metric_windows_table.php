<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PU-52 : agrégats par fenêtre de 10 minutes (UTC), rejouables sans doublon. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instance_id');
            $table->timestamp('window_start');
            $table->string('metric_name');
            $table->decimal('metric_value', 16, 2)->default(0);
            $table->timestamps();

            $table->foreign('instance_id')->references('id')->on('instances')->onDelete('cascade');
            $table->unique(['instance_id', 'window_start', 'metric_name']);
            $table->index(['window_start', 'metric_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_windows');
    }
};
