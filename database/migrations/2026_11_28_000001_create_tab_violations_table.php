<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('violation_type', 32);
            $table->string('page_url', 255)->nullable();
            $table->string('details', 500)->nullable();
            $table->boolean('is_blocked')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_violations');
    }
};
