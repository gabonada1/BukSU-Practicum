<?php

namespace Database\Seeders;

use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = PartnerCompany::query()->firstOrCreate(
            ['name' => 'University Practicum Technology Solutions'],
            [
                'industry' => 'Information Technology',
                'address' => 'Malaybalay City, Bukidnon',
                'contact_person' => 'System Administrator',
                'contact_email' => 'tech@technology.localhost',
                'contact_phone' => '09123456789',
                'intern_slot_limit' => 25,
                'is_active' => true,
            ]
        );

        TenantAdmin::query()->firstOrCreate(
            ['email' => 'admin@technology.localhost'],
            [
                'name' => 'University Practicum Internship Coordinator',
                'password' => 'password123',
            ]
        );

        Supervisor::query()->firstOrCreate(
            ['email' => 'supervisor@technology.localhost'],
            [
                'name' => 'Company Supervisor',
                'position' => 'IT Supervisor',
                'partner_company_id' => $company->id,
                'password' => 'password123',
            ]
        );

        $student = Student::query()->firstOrCreate(
            ['email' => 'student@technology.localhost'],
            [
                'student_number' => '2024-0001',
                'first_name' => 'Tech',
                'last_name' => 'Student',
                'password' => 'password123',
                'program' => 'BS Information Technology',
                'required_hours' => 486,
                'completed_hours' => 40,
                'status' => 'deployed',
                'partner_company_id' => $company->id,
            ]
        );

        StudentRequirement::query()->firstOrCreate(
            [
                'student_id' => $student->id,
                'requirement_name' => 'Resume',
            ],
            [
                'status' => 'approved',
                'notes' => 'Initial approved submission.',
                'submitted_at' => now(),
                'reviewed_at' => now(),
            ]
        );
    }
}
