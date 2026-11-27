<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problem_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('problem_id')->constrained('judge_problems')->cascadeOnDelete();
            $table->string('violation_type', 16);
            $table->boolean('is_blocked')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'problem_id', 'is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_blocks');
    }
};
