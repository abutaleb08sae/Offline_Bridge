<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_problems', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('time_limit')->default(2);
            $table->unsignedInteger('memory_limit')->default(256);
            $table->timestamps();
        });

        Schema::create('judge_test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_problem_id')->constrained('judge_problems')->cascadeOnDelete();
            $table->longText('input');
            $table->longText('expected_output');
            $table->timestamps();
        });

        Schema::create('judge_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('judge_problem_id')->constrained('judge_problems')->cascadeOnDelete();
            $table->string('language', 16);
            $table->longText('code');
            $table->string('verdict');
            $table->longText('error_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_submissions');
        Schema::dropIfExists('judge_test_cases');
        Schema::dropIfExists('judge_problems');
    }
};
