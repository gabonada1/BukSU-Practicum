<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('tenancy.tenant_connection', 'tenant');

        if (! Schema::connection($connection)->hasTable('tenant_users')
            || Schema::connection($connection)->hasColumn('tenant_users', 'must_change_password')) {
            return;
        }

        Schema::connection($connection)->table('tenant_users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        $connection = config('tenancy.tenant_connection', 'tenant');

        if (! Schema::connection($connection)->hasTable('tenant_users')
            || ! Schema::connection($connection)->hasColumn('tenant_users', 'must_change_password')) {
            return;
        }

        Schema::connection($connection)->table('tenant_users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
