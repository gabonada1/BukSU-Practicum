@php
    $layoutMode = 'dashboard';
    $courseFormContext = old('form_context');
    $editingCourseId = old('editing_course_id');
    $profileInfoFields = match ($profileRole) {
        'student' => ['first_name', 'last_name', 'email', 'program'],
        'supervisor' => ['name', 'email', 'position'],
        default => ['name', 'email'],
    };
    $profileInfoHasErrors = collect($profileInfoFields)->contains(fn ($field) => $errors->has($field));
    $passwordHasErrors = $errors->has('current_password')
        || $errors->has('password')
        || $errors->has('password_confirmation');
    $brandingHasErrors = $errors->has('portal_title')
        || $errors->has('accent_color')
        || $errors->has('secondary_color')
        || $errors->has('page_color')
        || $errors->has('page_alt_color')
        || $errors->has('surface_color')
        || $errors->has('surface_soft_color')
        || $errors->has('surface_alt_color')
        || $errors->has('text_color')
        || $errors->has('text_muted_color')
        || $errors->has('border_color')
        || $errors->has('portal_logo');
    $ojtSettingsHasErrors = $errors->has('default_ojt_hours')
        || $errors->has('allow_student_hour_override')
        || $errors->has('ojt_hours_note');
    $courseCreateHasErrors = $courseFormContext === 'course-create';
    $courseEditHasErrors = $courseFormContext === 'course-edit' && filled($editingCourseId);
    $brandingPortalTitle = old('portal_title', $brandingSettings['portal_title']);
    $brandingAccent = old('accent_color', $brandingSettings['accent']);
    $brandingSecondary = old('secondary_color', $brandingSettings['secondary']);
    $brandingPage = old('page_color', $brandingSettings['page']);
    $brandingPageAlt = old('page_alt_color', $brandingSettings['page_alt']);
    $brandingSurface = old('surface_color', $brandingSettings['surface']);
    $brandingSurfaceSoft = old('surface_soft_color', $brandingSettings['surface_soft']);
    $brandingSurfaceAlt = old('surface_alt_color', $brandingSettings['surface_alt']);
    $brandingText = old('text_color', $brandingSettings['text']);
    $brandingTextMuted = old('text_muted_color', $brandingSettings['text_muted']);
    $brandingBorder = old('border_color', $brandingSettings['border']);
    $brandingDefaultPortalTitle = config('app.name', 'University Practicum');
    $brandingDefaults = [
        'portal_title' => $brandingDefaultPortalTitle,
        'accent_color' => '#7B1C2E',
        'secondary_color' => '#F5A623',
        'page_color' => '#09111F',
        'page_alt_color' => '#0E1830',
        'surface_color' => '#0F172A',
        'surface_soft_color' => '#16213B',
        'surface_alt_color' => '#1B2946',
        'text_color' => '#EEF4FF',
        'text_muted_color' => '#9EABC5',
        'border_color' => '#8094C4',
    ];
    $profileTitle = $profileRole === 'admin' ? 'Profile' : ($profileRole === 'supervisor' ? 'Supervisor Profile' : 'Student Profile');
    $profileSubtitle = $profileRole === 'admin'
        ? 'Internship Coordinator account for '.$tenant->name
        : ($profileRole === 'supervisor' ? 'Company Supervisor account for '.$tenant->name : 'Student account for '.$tenant->name);
@endphp

@extends('layouts.tenant')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Some profile updates did not complete.</strong>
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

    <section class="content-grid profile-summary-grid" style="grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Overview</span>
                    <h2>Important Information</h2>
                    <p>Keep the essentials visible here, then use the action panel for changes.</p>
                </div>
                <span class="status-pill">{{ strtoupper($profileRole) }}</span>
            </div>

            <div class="profile-detail-grid">
                @if ($profileRole === 'student')
                    <div class="profile-detail-card">
                        <span>Full Name</span>
                        <strong>{{ trim($profileUser->first_name.' '.$profileUser->last_name) }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Email</span>
                        <strong>{{ $profileUser->email }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Student Number</span>
                        <strong>{{ $profileUser->student_number }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Course / Program</span>
                        <strong>
                            @if ($profileUser->course_id && $profileUser->course)
                                {{ $profileUser->course->code }} - {{ $profileUser->course->name }}
                            @else
                                {{ $profileUser->program ?: 'Not set yet' }}
                            @endif
                        </strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Status</span>
                        <strong>{{ ucfirst($profileUser->status) }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Progress</span>
                        <strong>{{ number_format($profileUser->completed_hours, 0) }} / {{ number_format($profileUser->required_hours, 0) }} hrs</strong>
                    </div>
                @elseif ($profileRole === 'supervisor')
                    <div class="profile-detail-card">
                        <span>Name</span>
                        <strong>{{ $profileUser->name }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Email</span>
                        <strong>{{ $profileUser->email }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Position</span>
                        <strong>{{ $profileUser->position ?: 'Not set yet' }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Organization</span>
                        <strong>{{ $profileUser->partnerCompany?->name ?: 'Unassigned' }}</strong>
                    </div>
                @else
                    <div class="profile-detail-card">
                        <span>Name</span>
                        <strong>{{ $profileUser->name }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Email</span>
                        <strong>{{ $profileUser->email }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>College</span>
                        <strong>{{ $tenant->name }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Portal Title</span>
                        <strong>{{ $brandingSettings['portal_title'] }}</strong>
                    </div>
                @endif
            </div>
        </article>

        <article class="section-card profile-actions-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Actions</span>
                    <h2>Manage Profile</h2>
                    <p>Open the action you need without crowding the page.</p>
                </div>
            </div>

            <div class="profile-action-list">
                <button type="button" class="profile-action-button profile-action-button-primary" data-modal-open="profile-info-modal">Edit Info</button>
                <button type="button" class="profile-action-button" data-modal-open="password-modal">Change Password</button>

                @if ($profileRole === 'admin')
                    <button type="button" class="profile-action-button" data-modal-open="branding-modal">Customize UI</button>
                    <button type="button" class="profile-action-button" data-modal-open="ojt-settings-modal">Edit OJT Settings</button>
                    <button type="button" class="profile-action-button" data-modal-open="course-create-modal">Add Course</button>
                @endif
            </div>

            @if ($profileRole === 'admin')
                <div class="profile-mini-grid">
                    <div class="profile-detail-card">
                        <span>Accent</span>
                        <strong>{{ strtoupper($brandingSettings['accent']) }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Secondary</span>
                        <strong>{{ strtoupper($brandingSettings['secondary']) }}</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Default OJT Hours</span>
                        <strong>{{ number_format($ojtSettings['default_ojt_hours'], 0) }} hrs</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Courses</span>
                        <strong>{{ $courses->count() }}</strong>
                    </div>
                </div>
            @endif
        </article>
    </section>

    @if ($profileRole === 'admin' && $courses->isNotEmpty())
        <section class="section-card" id="courses">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Course List</span>
                    <h2>Courses & Programs</h2>
                    <p>Quick view of your course catalog. Use the action buttons for updates.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Required OJT Hours</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td><strong>{{ $course->code }}</strong></td>
                                <td>{{ $course->name }}</td>
                                <td><span class="table-badge">{{ number_format($course->required_ojt_hours, 0) }} hrs</span></td>
                                <td>{{ $course->students_count }}</td>
                                <td><span class="table-badge">{{ $course->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <div class="link-row">
                                        <button type="button" class="action-icon-button action-icon-button-secondary" data-modal-open="course-edit-modal-{{ $course->id }}" title="Edit course" aria-label="Edit course">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="sr-only">Edit</span>
                                        </button>

                                        @if ($course->students_count === 0)
                                            <form
                                                method="POST"
                                                action="{{ $courseActions[$course->id]['destroy'] }}"
                                                data-confirm
                                                data-confirm-title="Remove course?"
                                                data-confirm-message="Remove course {{ $course->code }}?"
                                                data-confirm-submit-label="Remove course"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon-button action-icon-button-danger" title="Remove course" aria-label="Remove course">
                                                    <i class="fa-solid fa-trash"></i>
                                                    <span class="sr-only">Remove</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="metric-pill">Has students</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div id="profile-info-modal" class="modal-shell" hidden aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="profile-info-modal-title">
            <div class="modal-header">
                <div>
                    <h3 id="profile-info-modal-title">Edit Account Info</h3>
                    <p>Update the fields that belong to your role, then save your profile changes.</p>
                </div>
                <button type="button" class="modal-close-button" data-modal-close aria-label="Close account info modal">&times;</button>
            </div>

            <form method="POST" action="{{ $profileUpdateAction }}">
                @csrf
                @method('PATCH')

                <div class="form-grid">
                @if ($profileRole === 'student')
                    <label>
                        First Name
                        <input type="text" name="first_name" value="{{ old('first_name', $profileUser->first_name) }}" required>
                    </label>
                    <label>
                        Last Name
                        <input type="text" name="last_name" value="{{ old('last_name', $profileUser->last_name) }}" required>
                    </label>
                    <label class="field-span-2">
                        Email
                        <input type="email" name="email" value="{{ old('email', $profileUser->email) }}" required>
                    </label>

                    @if ($profileUser->course_id && $profileUser->course)
                        <label class="field-span-2">
                            Course / Program
                            <input type="text" value="{{ $profileUser->course->code }} - {{ $profileUser->course->name }}" readonly>
                            <small>Your course is managed through coordinator enrollment settings.</small>
                        </label>
                    @else
                        <label class="field-span-2">
                            Program
                            <input type="text" name="program" value="{{ old('program', $profileUser->program) }}">
                        </label>
                    @endif
                @elseif ($profileRole === 'supervisor')
                    <label>
                        Name
                        <input type="text" name="name" value="{{ old('name', $profileUser->name) }}" required>
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email', $profileUser->email) }}" required>
                    </label>
                    <label class="field-span-2">
                        Position
                        <input type="text" name="position" value="{{ old('position', $profileUser->position) }}">
                    </label>
                @else
                    <label>
                        Name
                        <input type="text" name="name" value="{{ old('name', $profileUser->name) }}" required>
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email', $profileUser->email) }}" required>
                    </label>
                @endif
                </div>

                <div class="modal-actions">
                    <button type="submit">Save Profile</button>
                    <button type="button" class="button secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="password-modal" class="modal-shell" hidden aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="password-modal-title">
            <div class="modal-header">
                <div>
                    <h3 id="password-modal-title">Change Password</h3>
                    <p>Enter your current password first, then choose a new password for your account.</p>
                </div>
                <button type="button" class="modal-close-button" data-modal-close aria-label="Close password modal">&times;</button>
            </div>

            <form method="POST" action="{{ $passwordUpdateAction }}">
                @csrf
                @method('PUT')

                <label>
                    Current Password
                    <input type="password" name="current_password" required>
                </label>
                <label>
                    New Password
                    <input type="password" name="password" required>
                </label>
                <label>
                    Confirm Password
                    <input type="password" name="password_confirmation" required>
                </label>

                <div class="modal-actions">
                    <button type="submit">Update Password</button>
                    <button type="button" class="button secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @if ($profileRole === 'admin')
        <div id="branding-modal" class="modal-shell" hidden aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="branding-modal-title">
                <div class="modal-header">
                    <div>
                        <h3 id="branding-modal-title">Customize Portal Branding</h3>
                        <p>Update the title, accent colors, and logo used across your tenant portal screens.</p>
                    </div>
                    <button type="button" class="modal-close-button" data-modal-close aria-label="Close branding modal">&times;</button>
                </div>

                <form method="POST" action="{{ $brandingSettingsAction }}" enctype="multipart/form-data">
                    @csrf

                    <label>
                        Portal Title
                        <input type="text" name="portal_title" value="{{ $brandingPortalTitle }}" maxlength="120" required data-branding-title-input>
                        <small>Shown in the tenant sidebar, login screens, and portal preview labels.</small>
                    </label>

                    <div class="form-grid">
                    <label>
                        Accent Color
                        <div class="profile-color-input">
                            <input type="color" name="accent_color" value="{{ $brandingAccent }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="accent_color">{{ strtoupper($brandingAccent) }}</span>
                        </div>
                        <small>Used for primary buttons, active navigation, and highlights.</small>
                    </label>

                    <label>
                        Secondary Color
                        <div class="profile-color-input">
                            <input type="color" name="secondary_color" value="{{ $brandingSecondary }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="secondary_color">{{ strtoupper($brandingSecondary) }}</span>
                        </div>
                        <small>Used for supporting accents like badges and divider highlights.</small>
                    </label>

                    <label>
                        Page Background
                        <div class="profile-color-input">
                            <input type="color" name="page_color" value="{{ $brandingPage }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="page_color">{{ strtoupper($brandingPage) }}</span>
                        </div>
                        <small>Controls the overall tenant page background.</small>
                    </label>

                    <label>
                        Page Background Alt
                        <div class="profile-color-input">
                            <input type="color" name="page_alt_color" value="{{ $brandingPageAlt }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="page_alt_color">{{ strtoupper($brandingPageAlt) }}</span>
                        </div>
                        <small>Used in the gradient transition behind your screens.</small>
                    </label>

                    <label>
                        Surface Color
                        <div class="profile-color-input">
                            <input type="color" name="surface_color" value="{{ $brandingSurface }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="surface_color">{{ strtoupper($brandingSurface) }}</span>
                        </div>
                        <small>Main card, modal, and panel tone.</small>
                    </label>

                    <label>
                        Surface Soft
                        <div class="profile-color-input">
                            <input type="color" name="surface_soft_color" value="{{ $brandingSurfaceSoft }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="surface_soft_color">{{ strtoupper($brandingSurfaceSoft) }}</span>
                        </div>
                        <small>Used for softer panel states and layered sections.</small>
                    </label>

                    <label>
                        Surface Alt
                        <div class="profile-color-input">
                            <input type="color" name="surface_alt_color" value="{{ $brandingSurfaceAlt }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="surface_alt_color">{{ strtoupper($brandingSurfaceAlt) }}</span>
                        </div>
                        <small>Used in alternate surfaces like cards and table headers.</small>
                    </label>

                    <label>
                        Text Color
                        <div class="profile-color-input">
                            <input type="color" name="text_color" value="{{ $brandingText }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="text_color">{{ strtoupper($brandingText) }}</span>
                        </div>
                        <small>Main text color across the tenant UI.</small>
                    </label>

                    <label>
                        Muted Text
                        <div class="profile-color-input">
                            <input type="color" name="text_muted_color" value="{{ $brandingTextMuted }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="text_muted_color">{{ strtoupper($brandingTextMuted) }}</span>
                        </div>
                        <small>Used for helper text, subtitles, and secondary copy.</small>
                    </label>

                    <label>
                        Border Color
                        <div class="profile-color-input">
                            <input type="color" name="border_color" value="{{ $brandingBorder }}" class="profile-color-picker" required data-branding-color-input>
                            <span class="profile-color-value" data-color-value="border_color">{{ strtoupper($brandingBorder) }}</span>
                        </div>
                        <small>Controls lines, dividers, and card outlines.</small>
                    </label>
                    </div>

                    <label>
                        College Logo
                        <input type="file" name="portal_logo" accept="image/png,image/jpeg,image/webp">
                        <small>PNG, JPG, or WebP up to 2 MB. Leave empty to keep the current logo.</small>
                    </label>

                    <div class="profile-branding-preview">
                        <div class="profile-branding-copy">
                            <strong data-branding-preview-title>{{ $brandingPortalTitle }}</strong>
                            <p>Preview the values you are about to save.</p>
                            <div class="profile-swatch-row">
                                <span class="profile-swatch-chip" data-color-chip="accent_color">
                                    <span class="profile-swatch" style="background: {{ $brandingAccent }}" data-swatch-source="accent_color"></span>
                                    <span data-color-chip-label="accent_color">Accent {{ strtoupper($brandingAccent) }}</span>
                                </span>
                                <span class="profile-swatch-chip" data-color-chip="secondary_color">
                                    <span class="profile-swatch" style="background: {{ $brandingSecondary }}" data-swatch-source="secondary_color"></span>
                                    <span data-color-chip-label="secondary_color">Secondary {{ strtoupper($brandingSecondary) }}</span>
                                </span>
                                <span class="profile-swatch-chip" data-color-chip="page_color">
                                    <span class="profile-swatch" style="background: {{ $brandingPage }}" data-swatch-source="page_color"></span>
                                    <span data-color-chip-label="page_color">Page {{ strtoupper($brandingPage) }}</span>
                                </span>
                                <span class="profile-swatch-chip" data-color-chip="surface_color">
                                    <span class="profile-swatch" style="background: {{ $brandingSurface }}" data-swatch-source="surface_color"></span>
                                    <span data-color-chip-label="surface_color">Surface {{ strtoupper($brandingSurface) }}</span>
                                </span>
                                <span class="profile-swatch-chip" data-color-chip="text_color">
                                    <span class="profile-swatch" style="background: {{ $brandingText }}" data-swatch-source="text_color"></span>
                                    <span data-color-chip-label="text_color">Text {{ strtoupper($brandingText) }}</span>
                                </span>
                                <span class="profile-swatch-chip" data-color-chip="border_color">
                                    <span class="profile-swatch" style="background: {{ $brandingBorder }}" data-swatch-source="border_color"></span>
                                    <span data-color-chip-label="border_color">Border {{ strtoupper($brandingBorder) }}</span>
                                </span>
                            </div>
                        </div>

                        @if ($brandingSettings['logo_path'])
                            <img class="profile-branding-logo" src="{{ asset($brandingSettings['logo_path']) }}" alt="{{ $brandingPortalTitle }} Logo">
                        @else
                            <div class="profile-logo-fallback">{{ strtoupper($tenant->code ?: 'BK') }}</div>
                        @endif
                    </div>

                    <div class="modal-actions">
                        <div class="modal-actions-start">
                            <button type="button" class="button secondary" data-branding-reset>Reset to Default</button>
                        </div>
                        <button type="submit" class="button">Save Branding</button>
                        <button type="button" class="button secondary" data-modal-close>Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="ojt-settings-modal" class="modal-shell" hidden aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="ojt-settings-modal-title">
                <div class="modal-header">
                    <div>
                        <h3 id="ojt-settings-modal-title">Update OJT Hours Settings</h3>
                        <p>Save the college-wide fallback hours and policy that apply when students are not using a course-specific requirement.</p>
                    </div>
                    <button type="button" class="modal-close-button" data-modal-close aria-label="Close OJT settings modal">&times;</button>
                </div>

                <form method="POST" action="{{ $ojtSettingsAction }}">
                    @csrf

                    <label>
                        Default Required OJT Hours
                        <input type="number" name="default_ojt_hours" value="{{ old('default_ojt_hours', $ojtSettings['default_ojt_hours']) }}" min="1" max="9999" step="0.5" required>
                        <small>Applied to students who do not have a course assigned.</small>
                    </label>

                    <label class="checkline">
                        <input type="hidden" name="allow_student_hour_override" value="0">
                        <input type="checkbox" name="allow_student_hour_override" value="1" {{ old('allow_student_hour_override', $ojtSettings['allow_student_hour_override']) ? 'checked' : '' }}>
                        Allow coordinators to manually override individual student OJT hours
                    </label>

                    <label>
                        OJT Hours Note / Policy
                        <textarea name="ojt_hours_note" rows="3" maxlength="500" placeholder="e.g. Students must complete all hours in a single company. Split deployment requires Dean approval.">{{ old('ojt_hours_note', $ojtSettings['ojt_hours_note']) }}</textarea>
                    </label>

                    <div class="modal-actions">
                        <button type="submit">Save OJT Settings</button>
                        <button type="button" class="button secondary" data-modal-close>Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="course-create-modal" class="modal-shell" hidden aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="course-create-modal-title">
                <div class="modal-header">
                    <div>
                        <h3 id="course-create-modal-title">Add New Course</h3>
                        <p>Create an official program entry for this college so coordinators can assign it directly to students.</p>
                    </div>
                    <button type="button" class="modal-close-button" data-modal-close aria-label="Close add course modal">&times;</button>
                </div>

                <form method="POST" action="{{ $courseStoreAction }}">
                    @csrf
                    <input type="hidden" name="form_context" value="course-create">

                    <div class="form-grid">
                    <label>
                        Course Code
                        <input type="text" name="code" value="{{ $courseCreateHasErrors ? old('code') : '' }}" placeholder="e.g. BSIT" maxlength="30" required>
                        <small>Short identifier such as BSIT, BSCS, or BSCpE.</small>
                    </label>

                    <label>
                        Required OJT Hours
                        <input type="number" name="required_ojt_hours" value="{{ $courseCreateHasErrors ? old('required_ojt_hours', $ojtSettings['default_ojt_hours']) : $ojtSettings['default_ojt_hours'] }}" min="1" max="9999" step="0.5" required>
                    </label>

                    <label class="field-span-2">
                        Full Course Name
                        <input type="text" name="name" value="{{ $courseCreateHasErrors ? old('name') : '' }}" placeholder="e.g. Bachelor of Science in Information Technology" maxlength="255" required>
                    </label>

                    <label>
                        Sort Order
                        <input type="number" name="sort_order" value="{{ $courseCreateHasErrors ? old('sort_order', 0) : 0 }}" min="0">
                        <small>Lower numbers appear first.</small>
                    </label>

                    <label class="checkline">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ (string) old('is_active', '1') === '1' ? 'checked' : '' }}>
                        Active and selectable for new students
                    </label>
                    </div>

                    <div class="modal-actions">
                        <button type="submit">Save Course</button>
                        <button type="button" class="button secondary" data-modal-close>Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach ($courses as $course)
            @php
                $isEditingCourse = $courseEditHasErrors && (string) $editingCourseId === (string) $course->id;
                $editCourseIsActive = $isEditingCourse ? old('is_active', (int) $course->is_active) : (int) $course->is_active;
            @endphp
            <div id="course-edit-modal-{{ $course->id }}" class="modal-shell" hidden aria-hidden="true">
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="course-edit-modal-title-{{ $course->id }}">
                    <div class="modal-header">
                        <div>
                            <h3 id="course-edit-modal-title-{{ $course->id }}">Edit {{ $course->code }}</h3>
                            <p>Update the course details and required OJT hours for this official program entry.</p>
                        </div>
                        <button type="button" class="modal-close-button" data-modal-close aria-label="Close edit course modal">&times;</button>
                    </div>

                    <form method="POST" action="{{ $courseActions[$course->id]['update'] }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="form_context" value="course-edit">
                        <input type="hidden" name="editing_course_id" value="{{ $course->id }}">

                        <div class="form-grid">
                        <label>
                            Code
                            <input type="text" name="code" value="{{ $isEditingCourse ? old('code', $course->code) : $course->code }}" maxlength="30" required>
                        </label>

                        <label>
                            OJT Hours
                            <input type="number" name="required_ojt_hours" value="{{ $isEditingCourse ? old('required_ojt_hours', $course->required_ojt_hours) : $course->required_ojt_hours }}" min="1" max="9999" step="0.5" required>
                        </label>

                        <label class="field-span-2">
                            Full Name
                            <input type="text" name="name" value="{{ $isEditingCourse ? old('name', $course->name) : $course->name }}" maxlength="255" required>
                        </label>

                        <label>
                            Sort Order
                            <input type="number" name="sort_order" value="{{ $isEditingCourse ? old('sort_order', $course->sort_order) : $course->sort_order }}" min="0">
                        </label>

                        <label class="checkline">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ (string) $editCourseIsActive === '1' ? 'checked' : '' }}>
                            Active
                        </label>
                        </div>

                        <div class="modal-actions">
                            <button type="submit">Update Course</button>
                            <button type="button" class="button secondary" data-modal-close>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif

    <script>
        function syncModalState() {
            const hasOpenModal = document.querySelector('.modal-shell:not([hidden])');
            document.body.classList.toggle('modal-open', Boolean(hasOpenModal));
        }

        function openProfileModal(id) {
            const modal = document.getElementById(id);

            if (! modal) {
                return;
            }

            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            syncModalState();

            const focusTarget = modal.querySelector('input:not([type="hidden"]):not([readonly]), select, textarea, button');

            if (focusTarget) {
                setTimeout(function () {
                    focusTarget.focus();
                }, 0);
            }
        }

        function closeProfileModal(modal) {
            if (! modal) {
                return;
            }

            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            syncModalState();
        }

        document.addEventListener('click', function (event) {
            const openTrigger = event.target.closest('[data-modal-open]');

            if (openTrigger) {
                event.preventDefault();
                openProfileModal(openTrigger.getAttribute('data-modal-open'));
                return;
            }

            const closeTrigger = event.target.closest('[data-modal-close]');

            if (closeTrigger) {
                event.preventDefault();
                closeProfileModal(closeTrigger.closest('.modal-shell'));
                return;
            }

            if (event.target.classList.contains('modal-shell')) {
                closeProfileModal(event.target);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            const openModals = Array.from(document.querySelectorAll('.modal-shell:not([hidden])'));
            const activeModal = openModals.pop();

            if (activeModal) {
                closeProfileModal(activeModal);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const hasProfileInfoErrors = @json($profileInfoHasErrors);
            const hasPasswordErrors = @json($passwordHasErrors);
            const hasBrandingErrors = @json($brandingHasErrors);
            const hasOjtSettingsErrors = @json($ojtSettingsHasErrors);
            const hasCourseCreateErrors = @json($courseCreateHasErrors);
            const hasCourseEditErrors = @json($courseEditHasErrors);
            const editingCourseId = @json($editingCourseId);
            const brandingDefaults = @json($brandingDefaults);
            const brandingForm = document.querySelector('#branding-modal form');

            function syncBrandingPreview() {
                if (! brandingForm) {
                    return;
                }

                const titleInput = brandingForm.querySelector('[data-branding-title-input]');
                const previewTitle = brandingForm.querySelector('[data-branding-preview-title]');

                if (titleInput && previewTitle) {
                    previewTitle.textContent = titleInput.value.trim() || brandingDefaults.portal_title;
                }

                brandingForm.querySelectorAll('[data-branding-color-input]').forEach(function (input) {
                    const value = String(input.value || '').toUpperCase();
                    const valueChip = brandingForm.querySelector('[data-color-value="' + input.name + '"]');
                    const swatch = brandingForm.querySelector('[data-swatch-source="' + input.name + '"]');
                    const swatchLabel = brandingForm.querySelector('[data-color-chip-label="' + input.name + '"]');

                    if (valueChip) {
                        valueChip.textContent = value;
                    }

                    if (swatch) {
                        swatch.style.background = value;
                    }

                    if (swatchLabel) {
                        const label = swatchLabel.textContent.split(' ')[0];
                        swatchLabel.textContent = label + ' ' + value;
                    }
                });
            }

            if (brandingForm) {
                brandingForm.addEventListener('input', function (event) {
                    if (event.target.matches('[data-branding-title-input], [data-branding-color-input]')) {
                        syncBrandingPreview();
                    }
                });

                const resetTrigger = brandingForm.querySelector('[data-branding-reset]');

                if (resetTrigger) {
                    resetTrigger.addEventListener('click', function () {
                        Object.entries(brandingDefaults).forEach(function ([name, value]) {
                            const field = brandingForm.elements.namedItem(name);

                            if (field) {
                                field.value = value;
                            }
                        });

                        const logoField = brandingForm.elements.namedItem('portal_logo');

                        if (logoField) {
                            logoField.value = '';
                        }

                        syncBrandingPreview();
                    });
                }

                syncBrandingPreview();
            }

            if (hasProfileInfoErrors) {
                openProfileModal('profile-info-modal');
            }

            if (hasPasswordErrors) {
                openProfileModal('password-modal');
            }

            if (hasBrandingErrors) {
                window.location.hash = 'portal-branding';
                openProfileModal('branding-modal');
            }

            if (hasOjtSettingsErrors) {
                window.location.hash = 'ojt-settings';
                openProfileModal('ojt-settings-modal');
            }

            if (hasCourseCreateErrors) {
                window.location.hash = 'courses';
                openProfileModal('course-create-modal');
            }

            if (hasCourseEditErrors && editingCourseId) {
                window.location.hash = 'courses';
                openProfileModal('course-edit-modal-' + editingCourseId);
            }
        });
    </script>
@endsection

