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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('last_login_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'last_login_at')) {
                $columns[] = 'last_login_at';
            }
            if (Schema::hasColumn('users', 'status')) {
                $columns[] = 'status';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
