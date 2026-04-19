@php
    $layoutMode = 'dashboard';
    $currentSection = $currentSection ?? 'applications';
    $requirementOptions = ['Resume', 'MOA', 'Endorsement Letter', 'Weekly Report', 'Monthly Report', 'Clearance'];
    $approvedRequirements = $student->requirements->where('status', 'approved')->count();
    $approvedLogs = $student->hourLogs->where('status', 'approved')->count();
    $remainingHours = max(0, (float) $student->required_hours - (float) $student->completed_hours);
    $activeApplication = $student->applications->first(fn ($application) => in_array($application->status, ['pending', 'accepted', 'deployed'], true));
    $latestApplicationForDocuments = $activeApplication ?: $student->applications->sortByDesc(fn ($application) => $application->applied_at?->timestamp ?? 0)->first();
    $assignedSupervisors = $student->partnerCompany?->supervisors ?? collect();
    $sectionMeta = [
        'applications' => [
            'title' => 'Internship Applications',
            'description' => 'Choose a partner organization, review active submissions, and keep your application documents together.',
            'pill' => strtoupper($student->status),
        ],
        'requirements' => [
            'title' => 'Forms & Requirements',
            'description' => 'Upload school requirements and track coordinator feedback without leaving the portal.',
            'pill' => $approvedRequirements.' approved',
        ],
        'logs' => [
            'title' => 'Progress & Hours',
            'description' => 'Submit daily duty logs, monitor validated hours, and review your progress history.',
            'pill' => number_format($remainingHours, 0).' hrs left',
        ],
    ];
    $activeMeta = $sectionMeta[$currentSection] ?? $sectionMeta['applications'];
@endphp

@extends('layouts.tenant')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Some student actions did not complete.</strong>
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

    <section class="section-card">
        <div class="section-header">
            <div>
                <h2>{{ $activeMeta['title'] }}</h2>
                <p>{{ $activeMeta['description'] }}</p>
            </div>
            <span class="status-pill">{{ $activeMeta['pill'] }}</span>
        </div>

        @if ($currentSection === 'applications')
            <div class="dashboard-grid">
                <article class="dashboard-card">
                    <div class="section-header">
                        <div>
                            <h2>Apply for Internship</h2>
                            <p>{{ $activeApplication ? 'The form is locked while your active application is still under review.' : 'Submit your preferred internship placement and supporting files here.' }}</p>
                        </div>
                        <span class="status-pill">{{ $activeApplication ? 'LOCKED' : 'OPEN' }}</span>
                    </div>

                    @if (! $canSubmitApplications)
                        <div class="helper-note">
                            Internship application submission is currently disabled for students in this tenant.
                        </div>
                    @elseif ($activeApplication)
                        <div class="helper-note">
                            Your current active application is <strong>{{ strtoupper($activeApplication->status) }}</strong> for
                            {{ $activeApplication->partnerCompany?->name ?: 'your selected organization' }}.
                        </div>
                    @else
                        <form method="POST" action="{{ $studentApplicationAction }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-grid">
                                <label class="field-span-2">
                                    Partner Organization
                                    <select name="partner_company_id" required>
                                        <option value="">Select an organization</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected((string) old('partner_company_id') === (string) $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Position Applied <input type="text" name="position_applied" value="{{ old('position_applied') }}" placeholder="IT Support, Lab Assistant, Accounting Intern" required></label>
                                <label class="field-span-2">Student Notes <textarea name="student_notes" placeholder="Preferred schedule, availability, or application remarks">{{ old('student_notes') }}</textarea></label>
                                <label>Resume <input type="file" name="resume" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required></label>
                                <label>Endorsement Letter <input type="file" name="endorsement_letter" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
                                <label>MOA <input type="file" name="moa" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
                                <label>Clearance <input type="file" name="clearance" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
                            </div>

                            <div class="hero-actions">
                                <button type="submit">Submit Internship Application</button>
                            </div>
                        </form>
                    @endif
                </article>

                @if ($latestApplicationForDocuments)
                    <article class="dashboard-card">
                        <div class="section-header">
                            <div>
                                <h2>Your Latest Documents</h2>
                                <p>Quick access to the most recent uploaded files.</p>
                            </div>
                            <span class="status-pill">{{ strtoupper($latestApplicationForDocuments->status) }}</span>
                        </div>

                        <div class="document-grid">
                            <div class="document-card">
                                <strong>Resume</strong>
                                <div class="doc-links">
                                    @if ($latestApplicationForDocuments->resume_path)
                                        <a class="doc-link" href="{{ asset($latestApplicationForDocuments->resume_path) }}" target="_blank" rel="noopener">Open uploaded file</a>
                                    @else
                                        <span>Not uploaded yet</span>
                                    @endif
                                </div>
                            </div>
                            <div class="document-card">
                                <strong>Endorsement Letter</strong>
                                <div class="doc-links">
                                    @if ($latestApplicationForDocuments->endorsement_letter_path)
                                        <a class="doc-link" href="{{ asset($latestApplicationForDocuments->endorsement_letter_path) }}" target="_blank" rel="noopener">Open uploaded file</a>
                                    @else
                                        <span>Not uploaded yet</span>
                                    @endif
                                </div>
                            </div>
                            <div class="document-card">
                                <strong>MOA</strong>
                                <div class="doc-links">
                                    @if ($latestApplicationForDocuments->moa_path)
                                        <a class="doc-link" href="{{ asset($latestApplicationForDocuments->moa_path) }}" target="_blank" rel="noopener">Open uploaded file</a>
                                    @else
                                        <span>Not uploaded yet</span>
                                    @endif
                                </div>
                            </div>
                            <div class="document-card">
                                <strong>Clearance</strong>
                                <div class="doc-links">
                                    @if ($latestApplicationForDocuments->clearance_path)
                                        <a class="doc-link" href="{{ asset($latestApplicationForDocuments->clearance_path) }}" target="_blank" rel="noopener">Open uploaded file</a>
                                    @else
                                        <span>Not uploaded yet</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endif

                <article class="dashboard-card">
                    <div class="table-header">
                        <div>
                            <h2>Your Application History</h2>
                            <p>{{ $student->applications->count() }} total submissions.</p>
                        </div>
                    </div>

                    @if ($student->applications->isEmpty())
                        <p>No internship applications yet.</p>
                    @else
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Applied</th>
                                        <th>Documents</th>
                                        <th>Feedback</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($student->applications as $application)
                                        <tr>
                                            <td>{{ $application->partnerCompany?->name ?: 'No organization' }}</td>
                                            <td>{{ $application->position_applied ?: 'Not set' }}</td>
                                            <td><span class="table-badge">{{ strtoupper($application->status) }}</span></td>
                                            <td>{{ $application->applied_at?->format('M d, Y') ?: 'Not set' }}</td>
                                            <td>
                                                <div class="doc-links">
                                                    @if ($application->resume_path)
                                                        <a class="doc-link" href="{{ asset($application->resume_path) }}" target="_blank" rel="noopener">Resume</a>
                                                    @endif
                                                    @if ($application->endorsement_letter_path)
                                                        <a class="doc-link" href="{{ asset($application->endorsement_letter_path) }}" target="_blank" rel="noopener">Endorsement</a>
                                                    @endif
                                                    @if ($application->moa_path)
                                                        <a class="doc-link" href="{{ asset($application->moa_path) }}" target="_blank" rel="noopener">MOA</a>
                                                    @endif
                                                    @if ($application->clearance_path)
                                                        <a class="doc-link" href="{{ asset($application->clearance_path) }}" target="_blank" rel="noopener">Clearance</a>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $application->admin_feedback ?: 'Waiting for coordinator review' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            </div>
        @elseif ($currentSection === 'requirements')
            <div class="content-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <article class="dashboard-card">
                    <div class="section-header">
                        <div>
                            <h2>Upload Form or Requirement</h2>
                            <p>Send documents to your coordinator with clear labels and notes.</p>
                        </div>
                    </div>

                    @if (! $canSubmitRequirements)
                        <div class="helper-note">
                            Requirement uploads are currently disabled for students in this tenant.
                        </div>
                    @else
                        <form method="POST" action="{{ $studentRequirementAction }}" enctype="multipart/form-data">
                            @csrf
                            <label>
                                Requirement Name
                                <select name="requirement_name" required>
                                    @foreach ($requirementOptions as $requirementOption)
                                        <option value="{{ $requirementOption }}" @selected(old('requirement_name', 'Resume') === $requirementOption)>{{ $requirementOption }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Notes <textarea name="notes" placeholder="Optional context for the coordinator reviewer">{{ old('notes') }}</textarea></label>
                            <label>File <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required></label>
                            <button type="submit">Upload Document</button>
                        </form>
                    @endif
                </article>

                <article class="dashboard-card">
                    <div class="section-header">
                        <div>
                            <h2>Submission Queue</h2>
                            <p>{{ $student->requirements->count() }} files in your document history.</p>
                        </div>
                    </div>

                    @if ($student->requirements->isEmpty())
                        <p>No forms or requirements submitted yet.</p>
                    @else
                        <ul class="clean-list">
                            @foreach ($student->requirements as $requirement)
                                <li>
                                    <strong>{{ $requirement->requirement_name }}</strong>
                                    <p><span class="table-badge">{{ strtoupper($requirement->status === 'revision' ? 'requires revision' : $requirement->status) }}</span></p>
                                    @if ($requirement->file_path)
                                        <p><a class="doc-link" href="{{ asset($requirement->file_path) }}" target="_blank" rel="noopener">Open uploaded file</a></p>
                                    @endif
                                    @if ($requirement->feedback)
                                        <p>Feedback: {{ $requirement->feedback }}</p>
                                    @elseif ($requirement->notes)
                                        <p>Notes: {{ $requirement->notes }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </div>
        @else
            <div class="content-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <article class="dashboard-card">
                    <div class="section-header">
                        <div>
                            <h2>Log OJT Hours</h2>
                            <p>Send your completed duty hours for review by your supervisor or internship coordinator.</p>
                        </div>
                        <span class="status-pill">{{ number_format($student->completed_hours, 0) }} / {{ number_format($student->required_hours, 0) }}</span>
                    </div>

                    <div class="profile-mini-grid">
                        <div class="profile-detail-card">
                            <span>Approved Hours</span>
                            <strong>{{ number_format($student->completed_hours, 0) }}</strong>
                        </div>
                        <div class="profile-detail-card">
                            <span>Remaining Hours</span>
                            <strong>{{ number_format($remainingHours, 0) }}</strong>
                        </div>
                        <div class="profile-detail-card">
                            <span>Approved Logs</span>
                            <strong>{{ $approvedLogs }}</strong>
                        </div>
                        <div class="profile-detail-card">
                            <span>Total Submissions</span>
                            <strong>{{ $student->hourLogs->count() }}</strong>
                        </div>
                    </div>

                    @if (! $canSubmitHourLogs)
                        <div class="helper-note">
                            Hour log submissions are currently disabled for students in this tenant.
                        </div>
                    @else
                        <form method="POST" action="{{ $studentHourLogAction }}">
                            @csrf
                            <div class="form-grid">
                                <label>Log Date <input type="date" name="log_date" value="{{ old('log_date', now()->toDateString()) }}" required></label>
                                <label>Hours Worked <input type="number" step="0.5" min="0.5" max="24" name="hours" value="{{ old('hours', 8) }}" required></label>
                                <label>
                                    Supervisor Name
                                    <select name="supervisor_name">
                                        <option value="">Select a supervisor</option>
                                        @foreach ($assignedSupervisors as $assignedSupervisor)
                                            <option value="{{ $assignedSupervisor->name }}" @selected(old('supervisor_name') === $assignedSupervisor->name)>{{ $assignedSupervisor->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="field-span-2">Activity <textarea name="activity" placeholder="Describe what you completed during this shift" required>{{ old('activity') }}</textarea></label>
                            </div>

                            <div class="hero-actions">
                                <button type="submit">Submit Hour Log</button>
                            </div>
                        </form>
                    @endif
                </article>

                <article class="dashboard-card">
                    <div class="section-header">
                        <div>
                            <h2>Progress & Hour History</h2>
                            <p>Your submitted logs stay visible here while admin and supervisors review them.</p>
                        </div>
                    </div>

                    @if ($student->hourLogs->isEmpty())
                        <p>No progress or hour logs yet.</p>
                    @else
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                        <th>Supervisor</th>
                                        <th>Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($student->hourLogs as $log)
                                        <tr>
                                            <td>{{ $log->log_date?->format('M d, Y') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($log->hours, 2), '0'), '.') }}</td>
                                            <td><span class="table-badge">{{ strtoupper($log->status) }}</span></td>
                                            <td>{{ $log->supervisor_name ?: 'Not set' }}</td>
                                            <td>{{ $log->activity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            </div>
        @endif
    </section>
@endsection
