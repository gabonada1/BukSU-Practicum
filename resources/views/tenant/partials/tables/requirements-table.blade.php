@php
    $embedded = $embedded ?? false;
    $showHeading = $showHeading ?? true;
@endphp

@unless ($embedded)
<article>
@endunless
    @if ($showHeading)
        <h2>Recent Forms & Requirements</h2>
    @endif
    @if ($requirements->isEmpty())
        <p>No practicum requirements submitted yet.</p>
    @else
        <table>
            <thead><tr><th>Student</th><th>Requirement</th><th>Document</th><th>Status</th><th>Feedback</th><th>Action</th></tr></thead>
            <tbody>
                @foreach ($requirements as $requirement)
                    <tr>
                        <td>{{ $requirement->student?->full_name ?: 'Unknown student' }}</td>
                        <td>{{ $requirement->requirement_name }}</td>
                        <td>
                            @if ($requirement->file_path)
                                <a class="action-icon-button document-action-button" href="{{ asset($requirement->file_path) }}" target="_blank" rel="noopener" title="Open file" aria-label="Open file">
                                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                                    <span>Open</span>
                                </a>
                            @else
                                No file
                            @endif
                        </td>
                        <td><span>{{ $requirement->status }}</span></td>
                        <td>{{ $requirement->feedback ?: ($requirement->notes ?: 'No feedback yet') }}</td>
                        <td>
                            <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=requirements&edit='.$requirement->id }}" title="Edit requirement" aria-label="Edit requirement">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span class="sr-only">Edit</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($requirements->hasPages())
            <div class="pagination">
                {{ $requirements->links() }}
            </div>
        @endif
    @endif
@unless ($embedded)
</article>
@endunless
