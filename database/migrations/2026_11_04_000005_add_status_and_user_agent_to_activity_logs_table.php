<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'status')) {
                $table->string('status')->default('info')->after('details');
            }
            if (!Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('activity_logs', 'status')) {
                $columns[] = 'status';
            }
            if (Schema::hasColumn('activity_logs', 'user_agent')) {
                $columns[] = 'user_agent';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

