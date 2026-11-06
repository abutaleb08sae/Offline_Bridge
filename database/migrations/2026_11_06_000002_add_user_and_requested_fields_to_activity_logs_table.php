<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('activity_logs', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('activity_logs', 'ip_address')) {
                $table->string('ip_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            $columns = [];
            foreach (['description', 'ip_address'] as $column) {
                if (Schema::hasColumn('activity_logs', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
