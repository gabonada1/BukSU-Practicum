<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Tests\TestCase;

class TenantPlanFeaturesTest extends TestCase
{
    public function test_basic_plan_cannot_customize_branding(): void
    {
        $tenant = new Tenant(['plan' => 'basic']);

        $this->assertFalse($tenant->canCustomizeBranding());
    }

    public function test_paid_upgrade_plans_can_customize_branding(): void
    {
        $this->assertTrue((new Tenant(['plan' => 'pro']))->canCustomizeBranding());
        $this->assertTrue((new Tenant(['plan' => 'premium']))->canCustomizeBranding());
    }
}
