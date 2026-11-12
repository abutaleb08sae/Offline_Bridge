<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judge_problems', function (Blueprint $table): void {
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('judge_problems', function (Blueprint $table): void {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
