<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instance_id');
            $table->string('type', 50);
            $table->string('key_hash', 64)->unique();
            $table->string('prefix', 20);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('instance_id')
                ->references('id')
                ->on('instances')
                ->onDelete('cascade');

            $table->index('instance_id');
            $table->index('key_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
