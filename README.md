# University Practicum

University Practicum is a multitenant Laravel practicum management system for Bukidnon State University. The system has a central administration area for provisioning and subscription management, plus separate tenant portals for colleges, students, supervisors, and internship coordinators.

## System Overview

- Product name: `University Practicum`
- Example institution: `Bukidnon State University`
- Framework: Laravel 12
- Architecture: MVC with Laravel Form Requests
- Central app: manages universities/tenants, plan applications, subscriptions, support tickets, and system releases
- Tenant app: manages OJT students, supervisors, partner companies, internship applications, requirements, hour logs, support, RBAC, and profile settings
- Tenant users: stored in one `tenant_users` table with role-based accounts
- Tenant roles: `admin`, `supervisor`, and `student`
- RBAC: enforced in the tenant portal

## MVC File Structure

This project follows Laravel's MVC structure.

```text
app
+-- Http
|   +-- Controllers
|   |   +-- Central
|   |   +-- Tenant
|   |   +-- Concerns
|   |   +-- Controller.php
|   +-- Middleware
|   +-- Requests
+-- Models
+-- Providers
+-- Services
+-- Support

resources
+-- views
    +-- central
    +-- tenant
    +-- layouts
    +-- components
    +-- partials

routes
+-- central.php
+-- tenant.php
+-- web.php
+-- console.php
```

## Controllers

Controllers are separated by application area.

```text
app/Http/Controllers/Central
```

Contains controllers for the central superadmin side:

- `CentralAuthController.php`
- `CentralDashboardController.php`
- `CentralLandingController.php`
- `PlanApplicationController.php`
- `SupportTicketController.php`
- `SystemUpdateController.php`
- `TenantProvisionController.php`

```text
app/Http/Controllers/Tenant
```

Contains controllers for the tenant portal side:

- `CourseController.php`
- `InternshipApplicationController.php`
- `OjtHourLogController.php`
- `PartnerCompanyController.php`
- `StudentController.php`
- `StudentDashboardController.php`
- `StudentRequirementController.php`
- `SupervisorController.php`
- `SupervisorDashboardController.php`
- `TenantAdminPasswordSetupController.php`
- `TenantAuthController.php`
- `TenantDashboardController.php`
- `TenantForgotPasswordController.php`
- `TenantProfileController.php`
- `TenantRbacController.php`
- `TenantRegistrationController.php`
- `TenantReleaseController.php`
- `TenantSupportController.php`
- `TenantUserManagementController.php`

```text
app/Http/Controllers/Concerns
```

Contains reusable controller traits:

- `AuthorizesTenantPermissions.php`
- `InteractsWithTenantRouting.php`
- `RecordsTenantAudit.php`

## Form Requests

Validation rules are separated from controllers into Laravel Form Request classes.

```text
app/Http/Requests
```

Examples:

- `StudentRequest.php`
- `SupervisorRequest.php`
- `CourseRequest.php`
- `PartnerCompanyRequest.php`
- `InternshipApplicationRequest.php`
- `SubmitInternshipApplicationRequest.php`
- `OjtHourLogRequest.php`
- `SubmitOjtHourLogRequest.php`
- `TenantLoginRequest.php`
- `TenantRegistrationRequest.php`
- `TenantProfileRequest.php`
- `TenantSupportRequest.php`
- `TenantProvisionRequest.php`
- `PlanApplicationRequest.php`
- `CentralLoginRequest.php`

Controllers receive these request classes through method injection and use:

```php
$validated = $request->validated();
```

## Models

Model classes are stored in:

```text
app/Models
```

This includes central and tenant-related models such as tenants, users, students, supervisors, partner companies, applications, requirements, support tickets, releases, and updates.

## Views

Blade views are stored in:

```text
resources/views
```

Important view folders:

- `resources/views/central` for central administration screens
- `resources/views/tenant` for tenant portal screens
- `resources/views/layouts` for shared layouts
- `resources/views/components` for reusable Blade components
- `resources/views/emails` for mail templates

## Routes

Routes are separated by area:

- `routes/web.php` loads the main entry route and includes central and tenant routes
- `routes/central.php` contains central superadmin, tenant provisioning, plan application, support, and system update routes
- `routes/tenant.php` contains tenant portal login, registration, dashboard, profile, OJT, support, RBAC, and user management routes

Tenant routes import controllers from:

```php
App\Http\Controllers\Tenant
```

Central routes import controllers from:

```php
App\Http\Controllers\Central
```

## Tenant Example

- University portal: `Bukidnon State University - College of Technologies`
- Tenant database: `buksu_college_of_technologies`
- Tenant domain: `technology.buksu.test`

## Environment

Use this tenant database block in `.env` and `.env.example`:

```env
TENANT_CONNECTION=tenant
TENANT_DB_CONNECTION=mysql
TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_DB_DATABASE=buksu_college_of_technologies
TENANT_DB_USERNAME=root
TENANT_DB_PASSWORD=
TENANT_DOMAIN=technology.buksu.test
```

Recommended related values:

```env
APP_NAME="University Practicum"
CENTRAL_SUPERADMIN_NAME="Bukidnon State University Superadmin"
CENTRAL_SUPERADMIN_EMAIL=superadmin@buksu.test
CENTRAL_SUPERADMIN_PASSWORD=password123
CENTRAL_DB_DATABASE=buksu_central
TENANCY_DEFAULT_TENANT=technology
```

## Local Bootstrapping

1. Create the central database: `buksu_central`.
2. Create the tenant database: `buksu_college_of_technologies`.
3. Copy `.env.example` to `.env` if needed.
4. Update the central and tenant database values in `.env`.
5. Run:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Development Commands

Run the Laravel development stack:

```bash
composer run dev
```

Run tests:

```bash
composer test
```

Check PHP syntax:

```bash
php -l path/to/file.php
```

## Default Access

Development central superadmin:

```text
Email: superadmin@buksu.test
Password: password123
```

This account is for local development only.

## Main Tenant Features

- Tenant login by role
- Student and supervisor registration
- Email verification
- Forgot password and reset code flow
- Tenant admin password setup
- Tenant dashboard
- Student dashboard
- Supervisor dashboard
- Student management
- Supervisor management
- Partner company management
- Internship application management
- Student requirement upload and review
- OJT hour log submission and review
- Tenant RBAC settings
- Tenant support tickets
- Tenant release/update management
- Tenant profile and branding settings

## Main Central Features

- Central superadmin login
- Public plan application flow
- Stripe checkout support
- Tenant approval and provisioning
- Tenant subscription settings
- Tenant activation and suspension
- Tenant notification emails
- Central support ticket review
- System release and update management
- Central dashboard

## Notes

- Tenant user data is consolidated by `database/migrations/tenant/2026_04_03_000017_consolidate_tenant_users_into_single_table.php`.
- Student-related records still use `student_id`, but now reference `tenant_users.id`.
- Tenant RBAC settings are stored on the tenant record and enforced in tenant-side controllers.
- Request validation is now handled through `app/Http/Requests` instead of inline controller validation.
- Tenant controllers are now grouped under `app/Http/Controllers/Tenant`.
- Central controllers remain grouped under `app/Http/Controllers/Central`.

## Verification Status

The project was checked after separating Form Requests and reorganizing tenant controllers.

```bash
composer test
```

Latest result:

```text
Tests: 9 passed, 14 assertions
```
