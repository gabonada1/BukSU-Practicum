<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacAbilitiesTest extends TestCase
{
    public function test_active_tenant_admin_can_manage_tenant_users(): void
    {
        $admin = new TenantAdmin([
            'name' => 'Tenant Admin',
            'email' => 'admin@example.test',
            'password' => 'secret123',
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $this->assertTrue(
            Gate::forUser($admin)->allows('manage-tenant-users')
        );
    }

    public function test_suspended_tenant_admin_cannot_manage_tenant_users(): void
    {
        $admin = new TenantAdmin([
            'name' => 'Suspended Admin',
            'email' => 'suspended@example.test',
            'password' => 'secret123',
            'is_active' => false,
            'suspended_at' => now(),
        ]);

        $this->assertFalse(
            Gate::forUser($admin)->allows('manage-tenant-users')
        );
    }

    public function test_role_specific_models_receive_their_role_on_instantiation(): void
    {
        $this->assertSame('admin', (new TenantAdmin())->role);
        $this->assertSame('supervisor', (new Supervisor())->role);
        $this->assertSame('student', (new Student())->role);
    }
}
