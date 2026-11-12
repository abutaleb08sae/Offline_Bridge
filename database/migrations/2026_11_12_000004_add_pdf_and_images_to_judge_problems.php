<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judge_problems', function (Blueprint $table): void {
            if (! Schema::hasColumn('judge_problems', 'pdf_path')) {
                $table->string('pdf_path')->nullable();
            }

            if (! Schema::hasColumn('judge_problems', 'images')) {
                $table->json('images')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('judge_problems', function (Blueprint $table): void {
            if (Schema::hasColumn('judge_problems', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }

            if (Schema::hasColumn('judge_problems', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};
