<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('judge_submissions', 'contest_id')) {
            Schema::table('judge_submissions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('contest_id');
            });
        }

        Schema::dropIfExists('contest_judge_problem');
        Schema::dropIfExists('contests');
    }

    public function down(): void
    {
        Schema::create('contests', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contest_judge_problem', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_problem_id')->constrained('judge_problems')->cascadeOnDelete();
            $table->string('label', 16);
            $table->unsignedInteger('points')->default(100);
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['contest_id', 'judge_problem_id']);
            $table->unique(['contest_id', 'label']);
            $table->index(['contest_id', 'position']);
        });

        Schema::table('judge_submissions', function (Blueprint $table): void {
            $table->foreignId('contest_id')->nullable()->after('judge_problem_id')
                ->constrained('contests')->nullOnDelete();
        });
    }
};
