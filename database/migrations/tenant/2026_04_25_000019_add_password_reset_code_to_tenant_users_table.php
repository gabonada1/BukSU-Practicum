<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('tenancy.tenant_connection', 'tenant');

        if (! Schema::connection($connection)->hasTable('tenant_users')) {
            return;
        }

        Schema::connection($connection)->table('tenant_users', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('tenant_users', 'password_reset_code')) {
                $table->string('password_reset_code')->nullable()->after('remember_token');
            }

            if (! Schema::connection($connection)->hasColumn('tenant_users', 'password_reset_expires_at')) {
                $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_code');
            }
        });
    }

    public function down(): void
    {
        $connection = config('tenancy.tenant_connection', 'tenant');

        if (! Schema::connection($connection)->hasTable('tenant_users')) {
            return;
        }

        Schema::connection($connection)->table('tenant_users', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('tenant_users', 'password_reset_expires_at')) {
                $table->dropColumn('password_reset_expires_at');
            }

            if (Schema::connection($connection)->hasColumn('tenant_users', 'password_reset_code')) {
                $table->dropColumn('password_reset_code');
            }
        });
    }
};
