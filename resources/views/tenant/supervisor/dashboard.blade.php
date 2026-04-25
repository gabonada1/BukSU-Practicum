@php
    $layoutMode = 'dashboard';
    $currentSection = $currentSection ?? 'students';
@endphp

@extends('layouts.tenant')

@section('content')
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if ($currentSection === 'students')
        <section id="students" class="section-card">
            <div class="section-header">
                <div>
                    <h2>Students</h2>
                    <p>{{ $company?->name ?: 'No partner organization assigned yet.' }}</p>
                </div>
            </div>

            @if ($students->isEmpty())
                <p>No students enrolled yet.</p>
            @else
                <ul class="clean-list">
                    @foreach ($students as $student)
                        @php
                            $studentProgress = $student->required_hours > 0 ? min(100, (int) round(($student->completed_hours / $student->required_hours) * 100)) : 0;
                            $remainingHours = max(0, (float) $student->required_hours - (float) $student->completed_hours);
                        @endphp
                        <li>
                            <strong>{{ $student->full_name }}</strong>
                            <p><span class="table-badge">{{ strtoupper($student->status) }}</span></p>
                            <p>{{ number_format($student->completed_hours, 0) }} / {{ number_format($student->required_hours, 0) }} hours</p>
                            <p>{{ number_format($remainingHours, 0) }} hours remaining</p>
                            <details class="student-documents-panel">
                                <summary>View Documents</summary>
                                @if ($student->requirements->isEmpty())
                                    <p>No documents submitted yet.</p>
                                @else
                                    <div class="table-wrap student-documents-table-wrap">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Requirement</th>
                                                    <th>Document</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($student->requirements as $requirement)
                                                    <tr>
                                                        <td>{{ $requirement->requirement_name }}</td>
                                                        <td>
                                                            @if ($requirement->file_path)
                                                                <a class="doc-link" href="{{ asset($requirement->file_path) }}" target="_blank" rel="noopener">Open file</a>
                                                            @else
                                                                No file
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="table-badge">{{ strtoupper($requirement->status === 'revision' ? 'requires revision' : $requirement->status) }}</span>
                                                        </td>
                                                        <td>{{ $requirement->feedback ?: ($requirement->notes ?: 'No feedback yet') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </details>
                            <div class="progress-track"><span style="width: {{ $studentProgress }}%"></span></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @else
        <section id="logs" class="section-card">
            <div class="section-header">
                <div>
                    <h2>Progress & Hour Logs</h2>
                    <p>Submitted logs from students assigned to your company.</p>
                </div>
            </div>

            @if ($hourLogs->isEmpty())
                <p>No recent hour logs yet.</p>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Activity</th>
                                <th>Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hourLogs as $log)
                                <tr>
                                    <td>{{ $log->student?->full_name ?: 'Unknown student' }}</td>
                                    <td>{{ $log->log_date?->format('M d, Y') }}</td>
                                    <td>{{ rtrim(rtrim(number_format($log->hours, 2), '0'), '.') }}</td>
                                    <td><span class="table-badge">{{ strtoupper($log->status) }}</span></td>
                                    <td>{{ $log->activity }}</td>
                                    <td>
                                        <div class="hero-actions">
                                            <form method="POST" action="{{ route('tenant.supervisor.hours.update', ['hour' => $log]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="secondary">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('tenant.supervisor.hours.update', ['hour' => $log]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="danger">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
