# University Practicum

> Auto-generated project documentation. This file is refreshed by `php artisan docs:generate-project` and automatically updates when university tenant records are created, updated, or deleted through the application.

## 1. Project Summary

University Practicum is a multitenant Laravel application that separates the **University Administration application** from each **university portal**.

- The **University Administration application** runs with no tenant context and is used by Bukidnon State University Administration to manage university portals, license tiers, domains, and registration.
- Each **university portal** runs in tenant context and is used for practicum operations, role-based access, and university-specific records.

## 2. Architecture

### University Administration Application

- Uses the central database: `central` with central connection `central`.
- Main responsibilities:
- Authenticate Bukidnon State University Administration.
- Create and register university portals.
- Approve university applications and assign tenant access domains.
- Create tenant databases and launch tenant migrations.
- Maintain the university directory and launch links.

### University Portal Application

- Uses the tenant connection `tenant` after college resolution.
- Main responsibilities:
- Authenticate internship coordinators, company supervisors, and students.
- Manage partner companies, student applications, forms and requirements, progress reports, and evaluation workflows.
- Render university dashboards for each role.

## 3. Databases

- Central database connection: `central`.
- Tenant database connection: `tenant`.
- Base domain for generated university subdomains: `buksu.test`.
- Central domains: `127.0.0.1`, `localhost`.

### Central Database Stores

- University portal registry and metadata
- Approved tenant domain records
- Bukidnon State University admin accounts
- Shared app-level configuration

### University Portal Database Stores

- Internship coordinators
- Company supervisors
- Students
- Partner companies
- Student applications
- Forms and requirements
- Progress and hour logs
- Evaluation records

## 4. Authentication and Roles

### Central Role

- Bukidnon State University Administration
- Login: `/central/login` on a central domain such as `localhost` or `127.0.0.1`.

### University Portal Roles

- University Admin / Internship Coordinator: manages partner companies, reviews submissions, assigns students, tracks OJT hours, and reviews evaluations.
- Company Supervisor: accepts or rejects assigned students, logs attendance or hours, submits evaluation forms, and validates student reports.
- Student: views partner companies, applies for internship slots, uploads requirements, submits reports, and tracks OJT progress.
- Shared university portal login: `/login` on a tenant hostname such as `technology.buksu.test`.

## 5. Route Structure

- `routes/web.php`: top-level entry resolver.
- `routes/central.php`: central application routes.
- `routes/tenant.php`: university portal application routes.

### Important Central Routes

- `GET /` -> central app entry resolver
- `GET /central/login` -> University Administration login page
- `GET /central/dashboard` -> University Administration dashboard
- `POST /central/tenants` -> register a new university portal and its access metadata

### Important Tenant Routes

- `GET /` on a tenant hostname -> university portal entry
- `GET /login` on a tenant hostname -> university portal login page
- `GET /admin/dashboard` on a tenant hostname -> internship coordinator dashboard
- `GET /supervisor/dashboard` on a tenant hostname -> company supervisor dashboard
- `GET /student/dashboard` on a tenant hostname -> student dashboard

## 6. Provisioning Flow

When Bukidnon State University Administration registers a new university portal from the central dashboard, the application:

1. Saves the university metadata in the central database.
2. Stores any approved direct-access domains in the central domain registry.
3. Creates the tenant database if it does not yet exist.
4. Runs tenant migrations on the new database.
5. Creates the first internship coordinator account.
6. Refreshes this project documentation file automatically.

## 7. Current Managed Tenants

| University Portal | Code | License Tier | Approved Domains | Database | Status |
| --- | --- | --- | --- | --- | --- |
| Bukidnon State University | BSU | PREMIUM | univ, bsu.localhost, bsu.lvh.me, ustp.lvh.me | buksu_bukidnon_state_university | Active |
| College of Arts & Sciences | CAS | PREMIUM | arts-sciences.localhost | buksu_college_of_arts_and_sciences | Active |
| College of Business | COLLEG | PRO | cob.lvh.me, cob, colleg.lvh.me | buksu_college_of_business | Active |
| College of Business | COB | PREMIUM | business.localhost | buksu_college_of_business | Active |
| College of Education | COED | PREMIUM | education.localhost | buksu_college_of_education | Active |
| College of Nursing | CON | PREMIUM | nursing, nursing.localhost, con.lvh.me, nursing.lvh.me | buksu_college_of_nursing | Active |
| College of Public Administration | CPA | PREMIUM | public-admin.localhost | buksu_college_of_public_administration | Active |
| College of Technologies | COT | PREMIUM | cot, technology.localhost, cot.lvh.me | buksu_college_of_technologies | Active |
| NEMSU | N | PREMIUM | nemsu, nemsu.localhost | 18a0211aa63774306acaf2ae | Active |
| NEMSU | N | BASIC | ustp, ustp.localhost | 9b73a7218dacaafeef6e916e | Active |

## 8. Seeded Local Credentials

### Bukidnon State University Administration

- Email: `superadmin@buksu.test`
- Password: defined by `CENTRAL_SUPERADMIN_PASSWORD` in `.env`

## 9. Local Development

### XAMPP Workflow

- Start Apache and MySQL in XAMPP.
- Start the app with `php artisan serve --host=127.0.0.1 --port=8000`.
- Open the central app at `http://localhost:8000/central/login`.
- Open university portals with `http://technology.buksu.test/login` or another tenant hostname.
- Run `npm run build` only when frontend assets change.

### Maintenance Commands

```bash
php artisan migrate
php artisan db:seed
php artisan tenants:migrate 1
php artisan tenants:seed 1
php artisan docs:generate-project
npm run build
```

## 10. Documentation Automation

- This file is generated at: `docs/PROJECT_DOCUMENTATION.md`.
- Manual refresh command: `php artisan docs:generate-project`.
- Automatic refresh happens whenever a university tenant record is saved or deleted through the application.

## 11. Key Files

- Central dashboard controller: `app/Http/Controllers/Central/CentralDashboardController.php`
- Central provisioning controller: `app/Http/Controllers/Central/TenantProvisionController.php`
- Central auth controller: `app/Http/Controllers/Central/CentralAuthController.php`
- University portal auth controller: `app/Http/Controllers/TenantAuthController.php`
- Tenancy config: `config/tenancy.php`
- Central routes: `routes/central.php`
- Tenant routes: `routes/tenant.php`
