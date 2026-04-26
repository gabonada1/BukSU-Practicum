<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('tenancy.tenant_connection', 'tenant');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('tenant_users')) {
            $schema->create('tenant_users', function (Blueprint $table) {
                $table->id();
                $table->string('role');
                $table->string('name')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('student_number')->nullable()->unique();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->boolean('must_change_password')->default(false);
                $table->string('program')->nullable();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->decimal('required_hours', 6, 2)->default(486);
                $table->decimal('completed_hours', 6, 2)->default(0);
                $table->string('status')->default('pending');
                $table->foreignId('partner_company_id')->nullable()->constrained('partner_companies')->nullOnDelete();
                $table->string('position')->nullable();
                $table->string('department')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('suspended_at')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('email_verification_token')->nullable()->unique();
                $table->timestamp('verification_sent_at')->nullable();
                $table->timestamp('registered_at')->nullable();
                $table->boolean('registered_via_self_service')->default(false);
                $table->rememberToken();
                $table->string('password_reset_code')->nullable();
                $table->timestamp('password_reset_expires_at')->nullable();
                $table->timestamps();
                $table->index(['role', 'is_active']);
            });

            $this->copyLegacyUsers($connection);

            return;
        }

        $this->ensureColumn($connection, 'must_change_password', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
        $this->ensureColumn($connection, 'password_reset_code', function (Blueprint $table) {
            $table->string('password_reset_code')->nullable()->after('remember_token');
        });
        $this->ensureColumn($connection, 'password_reset_expires_at', function (Blueprint $table) {
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_code');
        });
    }

    public function down(): void
    {
        // Repair migration only. Do not drop tenant user data on rollback.
    }

    protected function ensureColumn(string $connection, string $column, callable $definition): void
    {
        if (Schema::connection($connection)->hasColumn('tenant_users', $column)) {
            return;
        }

        Schema::connection($connection)->table('tenant_users', $definition);
    }

    protected function copyLegacyUsers(string $connection): void
    {
        $this->copyStudents($connection);
        $this->copyTenantAdmins($connection);
        $this->copySupervisors($connection);
    }

    protected function copyStudents(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('students')) {
            return;
        }

        foreach (DB::connection($connection)->table('students')->get() as $student) {
            DB::connection($connection)->table('tenant_users')->updateOrInsert(
                ['email' => $student->email],
                [
                    'id' => $student->id,
                    'role' => 'student',
                    'name' => trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_number' => $student->student_number,
                    'password' => $student->password,
                    'must_change_password' => false,
                    'program' => $student->program,
                    'course_id' => $student->course_id ?? null,
                    'required_hours' => $student->required_hours,
                    'completed_hours' => $student->completed_hours,
                    'status' => $student->status,
                    'partner_company_id' => $student->partner_company_id,
                    'is_active' => $student->is_active ?? true,
                    'suspended_at' => $student->suspended_at ?? null,
                    'email_verified_at' => $student->email_verified_at ?? null,
                    'email_verification_token' => $student->email_verification_token ?? null,
                    'verification_sent_at' => $student->verification_sent_at ?? null,
                    'registered_at' => $student->registered_at ?? null,
                    'registered_via_self_service' => $student->registered_via_self_service ?? false,
                    'remember_token' => $student->remember_token ?? null,
                    'created_at' => $student->created_at ?? now(),
                    'updated_at' => $student->updated_at ?? now(),
                ]
            );
        }
    }

    protected function copyTenantAdmins(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('tenant_admins')) {
            return;
        }

        foreach (DB::connection($connection)->table('tenant_admins')->get() as $admin) {
            DB::connection($connection)->table('tenant_users')->updateOrInsert(
                ['email' => $admin->email],
                [
                    'role' => 'admin',
                    'name' => $admin->name,
                    'password' => $admin->password,
                    'must_change_password' => $admin->must_change_password ?? false,
                    'status' => 'active',
                    'is_active' => $admin->is_active ?? true,
                    'suspended_at' => $admin->suspended_at ?? null,
                    'email_verified_at' => $admin->created_at ?? now(),
                    'registered_at' => $admin->created_at ?? now(),
                    'registered_via_self_service' => false,
                    'remember_token' => $admin->remember_token ?? null,
                    'created_at' => $admin->created_at ?? now(),
                    'updated_at' => $admin->updated_at ?? now(),
                ]
            );
        }
    }

    protected function copySupervisors(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('supervisors')) {
            return;
        }

        foreach (DB::connection($connection)->table('supervisors')->get() as $supervisor) {
            DB::connection($connection)->table('tenant_users')->updateOrInsert(
                ['email' => $supervisor->email],
                [
                    'role' => 'supervisor',
                    'name' => $supervisor->name,
                    'password' => $supervisor->password,
                    'must_change_password' => false,
                    'partner_company_id' => $supervisor->partner_company_id,
                    'position' => $supervisor->position ?? null,
                    'department' => $supervisor->department ?? null,
                    'status' => 'active',
                    'is_active' => $supervisor->is_active ?? true,
                    'suspended_at' => $supervisor->suspended_at ?? null,
                    'email_verified_at' => $supervisor->email_verified_at ?? null,
                    'email_verification_token' => $supervisor->email_verification_token ?? null,
                    'verification_sent_at' => $supervisor->verification_sent_at ?? null,
                    'registered_at' => $supervisor->registered_at ?? null,
                    'registered_via_self_service' => $supervisor->registered_via_self_service ?? false,
                    'remember_token' => $supervisor->remember_token ?? null,
                    'created_at' => $supervisor->created_at ?? now(),
                    'updated_at' => $supervisor->updated_at ?? now(),
                ]
            );
        }
    }
};
