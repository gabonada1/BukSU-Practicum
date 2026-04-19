@php
    $layoutMode = 'dashboard';
    $currentSection = request()->query('section', 'companies');
    $createSection = request()->query('create');

    $sections = [
        'companies' => [
            'title' => 'Partner Organizations',
            'create_title' => 'Partner Organization',
            'empty' => 'No partner organizations on file yet.',
            'table' => 'tenant.partials.tables.partner-companies-table',
            'form' => 'tenant.partials.forms.partner-company-form',
        ],
        'supervisors' => [
            'title' => 'Company Supervisors',
            'create_title' => 'Company Supervisor',
            'empty' => 'No company supervisors registered yet.',
            'table' => 'tenant.partials.tables.supervisors-table',
            'form' => 'tenant.partials.forms.supervisor-form',
        ],
        'students' => [
            'title' => 'Students',
            'create_title' => 'Student',
            'empty' => 'No students yet.',
            'table' => 'tenant.partials.tables.students-table',
            'form' => 'tenant.partials.forms.student-form',
        ],
        'users' => [
            'title' => 'User Management',
            'empty' => 'No university portal users yet.',
            'table' => 'tenant.partials.tables.users-table',
            'form' => 'tenant.partials.forms.user-management-form',
        ],
        'requirements' => [
            'title' => 'Forms & Requirements',
            'create_title' => 'Form / Requirement',
            'empty' => 'No forms or requirements submitted yet.',
            'table' => 'tenant.partials.tables.requirements-table',
            'form' => 'tenant.partials.forms.requirement-form',
        ],
        'hours' => [
            'title' => 'Progress & Hour Logs',
            'create_title' => 'Progress / Hour Log',
            'empty' => 'No progress or hour logs yet.',
            'table' => 'tenant.partials.tables.hour-logs-table',
            'form' => 'tenant.partials.forms.hour-log-form',
        ],
    ];

    if (! array_key_exists($currentSection, $sections)) {
        $currentSection = 'companies';
    }

    $section = $sections[$currentSection];
    $editingCompany = $editing['companies'] ?? null;
    $editingSupervisor = $editing['supervisors'] ?? null;
    $editingStudent = $editing['students'] ?? null;
    $editingRequirement = $editing['requirements'] ?? null;
    $editingHour = $editing['hours'] ?? null;
    $editingUser = $editing['users'] ?? null;
    $showCreatePanel = ($createSection === $currentSection || $errors->any()) && $currentSection !== 'users';
    $showEditPanel = filled(match ($currentSection) {
        'companies' => $editingCompany,
        'supervisors' => $editingSupervisor,
        'students' => $editingStudent,
        'requirements' => $editingRequirement,
        'hours' => $editingHour,
        'users' => $editingUser,
        default => null,
    });
    $dashboardBaseUrl = route('tenant.admin.dashboard');
    $baseSectionUrl = $dashboardBaseUrl.'?section='.$currentSection;
    $sectionCreateTitle = $section['create_title'] ?? \Illuminate\Support\Str::singular($section['title']);
    $sectionSummary = [
        'companies' => 'Review placement partners, open internship slots, and supervisor coverage from one workspace.',
        'supervisors' => 'Keep supervisor records complete so approvals, evaluations, and student monitoring stay organized.',
        'students' => 'Track student deployment, course assignment, and application history without leaving the dashboard.',
        'users' => 'Manage coordinator, supervisor, and student accounts inside this tenant workspace.',
        'requirements' => 'Review requirement submissions, monitor approval flow, and keep document compliance moving.',
        'hours' => 'Watch validated duty hours, keep logs accurate, and surface records that still need review.',
    ][$currentSection];
@endphp

@extends('layouts.tenant')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Some university portal updates did not complete.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <section class="section-card" id="{{ $currentSection }}">
        <div class="table-header">
            <div>
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $sectionSummary }}</p>
            </div>
            <div class="toolbar-actions">
                @if ($currentSection === 'users')
                    @if ($tenantPermissions['user.create'] ?? false)
                        <a class="panel-link" href="{{ $dashboardBaseUrl.'?section=students&create=students' }}">Add Student</a>
                    @endif
                    <a class="panel-link" href="{{ $rbacIndexUrl }}">RBAC</a>
                @else
                    <a class="panel-link" href="{{ $dashboardBaseUrl.'?section='.$currentSection.'&create='.$currentSection }}">Add Record</a>
                @endif
            </div>
        </div>

        <div class="dashboard-stack">
            @if ($showCreatePanel)
                <div class="dashboard-card dashboard-editor-card">
                    <div class="table-header">
                        <div>
                            <span class="mini-kicker">Create</span>
                            <h2>New {{ $sectionCreateTitle }}</h2>
                        </div>
                        <a class="panel-link" href="{{ $baseSectionUrl }}">Close</a>
                    </div>

                    @include($section['form'], ['embedded' => true, 'showHeading' => false])
                </div>
            @endif

            @if ($showEditPanel)
                <div class="dashboard-card dashboard-editor-card">
                    <div class="table-header">
                        <div>
                            <span class="mini-kicker">Edit</span>
                            <h2>Edit {{ $section['title'] === 'User Management' ? 'User' : $sectionCreateTitle }}</h2>
                        </div>
                        <a class="panel-link" href="{{ $baseSectionUrl }}">Close</a>
                    </div>

                    @include($section['form'], ['embedded' => true, 'showHeading' => false, 'mode' => 'edit'])
                </div>
            @endif

            <div class="dashboard-card dashboard-table-card">
                @include($section['table'], ['embedded' => true, 'showHeading' => false])
            </div>

            @if ($currentSection === 'students' && $selectedStudentForApplications)
                <div class="dashboard-card dashboard-table-card">
                    <div class="table-header">
                        <div>
                            <span class="mini-kicker">Student History</span>
                            <h2>Applications for {{ $selectedStudentForApplications->full_name }}</h2>
                        </div>
                        <a class="panel-link" href="{{ $baseSectionUrl }}">Close</a>
                    </div>

                    @include('tenant.partials.tables.applications-table', [
                        'embedded' => true,
                        'showHeading' => false,
                        'applications' => $selectedStudentApplications,
                        'applicationSection' => 'students',
                    ])
                </div>
            @endif
        </div>
    </section>
@endsection
