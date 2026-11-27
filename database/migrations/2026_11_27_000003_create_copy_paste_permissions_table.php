<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copy_paste_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('problem_id')->constrained('judge_problems')->cascadeOnDelete();
            $table->boolean('is_allowed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'problem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_paste_permissions');
    }
};
