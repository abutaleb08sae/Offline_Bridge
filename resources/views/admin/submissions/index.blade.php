@extends('layouts.app')

@section('title', 'Student Submissions')

@section('content')
<div id="submissionDashboard" style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Section 1: Submissions Metrics Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4);">
        <!-- Total Submissions -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
                        Total Submissions
                    </span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $totalSubmissions }}
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--primary-light); color: var(--primary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                Received across all active lab sessions
            </div>
        </div>

        <!-- Unique Students -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
                        Unique Students
                    </span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $uniqueStudents }}
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--color-success-bg); color: var(--color-success-text); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                Verified student IDs participating
            </div>
        </div>

        <!-- Latest Submission -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
                        Latest Submission
                    </span>
                    <div style="font-size: var(--font-size-base); font-weight: 700; margin-top: 0.25rem; color: var(--text-main);">
                        @if($latestSubmission)
                            {{ $latestSubmission->created_at->diffForHumans() }}
                        @else
                            No submissions yet
                        @endif
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--color-info-bg); color: var(--color-info-text); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                @if($latestSubmission)
                    From <strong style="color: var(--text-main);">{{ $latestSubmission->student_name }}</strong> ({{ $latestSubmission->client_ip }})
                @else
                    Awaiting client PC submissions
                @endif
            </div>
        </div>
    </div>

    <!-- Section 2: Submissions Table Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Student Solution Submissions</h3>
                <p class="card-subtitle">
                    Completed assignments received from client PCs over the local offline LAN
                </p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="search-input-wrapper" style="flex: 1;">
                <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" id="submissionSearchInput" class="form-input" placeholder="Search by student name, ID, task title, or client IP..." onkeyup="filterSubmissionsTable()">
            </div>
        </div>

        <!-- Submissions Table -->
        @if($submissions->isEmpty())
            <div style="text-align: center; padding: var(--space-8) var(--space-4); background-color: var(--bg-surface-hover); border-radius: var(--radius-lg); border: 1px dashed var(--border-color);">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin: 0 auto var(--space-3); color: var(--text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                </svg>
                <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-1);">
                    No Student Submissions Yet
                </h4>
                <p style="font-size: var(--font-size-xs); color: var(--text-muted); max-width: 480px; margin: 0 auto var(--space-4);">
                    When students submit their Packet Tracer solutions from client PCs via the <code style="color: var(--primary);">/submit</code> route, their work will appear here immediately.
                </p>
            </div>
        @else
            <div class="table-container">
                <table class="table" id="submissionsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Submission ID</th>
                            <th style="width: 170px;">Submission Time</th>
                            <th>Student Info</th>
                            <th style="width: 140px;">Client IP</th>
                            <th>Assigned Task</th>
                            <th>File Details</th>
                            <th style="text-align: right; width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $sub)
                            <tr class="submission-row">
                                <td style="font-family: monospace; color: var(--text-muted);">
                                    #{{ $sub->id }}
                                </td>

                                <!-- 1. Submission Time -->
                                <td>
                                    <div style="font-weight: 600; font-size: var(--font-size-xs); color: var(--text-main);">
                                        {{ $sub->created_at->format('d M Y, H:i:s') }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        {{ $sub->created_at->diffForHumans() }}
                                    </div>
                                </td>

                                <!-- 2. Student Info -->
                                <td>
                                    <div class="student-name-text" style="font-weight: 700; color: var(--text-main);">
                                        {{ $sub->student_name }}
                                    </div>
                                    <div class="student-id-text" style="font-family: monospace; font-size: var(--font-size-xs); color: var(--primary);">
                                        ID: {{ $sub->student_id }}
                                    </div>
                                </td>

                                <!-- 3. Client IP -->
                                <td>
                                    <code class="client-ip-text" style="background: var(--bg-surface-hover); padding: 0.2rem 0.45rem; border-radius: 4px; font-size: var(--font-size-xs); color: var(--text-main); border: 1px solid var(--border-color);">
                                        {{ $sub->client_ip }}
                                    </code>
                                </td>

                                <!-- 4. Assigned Task -->
                                <td>
                                    @if($sub->labTask)
                                        <div class="task-title-text" style="font-weight: 600; font-size: var(--font-size-xs); color: var(--text-main);">
                                            {{ $sub->labTask->title }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;">
                                            Original: {{ $sub->labTask->file_name }}
                                        </div>
                                    @else
                                        <span class="badge badge-neutral" style="font-size: 0.7rem;">Task Deleted</span>
                                    @endif
                                </td>

                                <!-- 5. File Details -->
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        @if(in_array($sub->file_extension, ['PKT', 'PKZ']))
                                            <span class="badge badge-success" style="font-size: 0.65rem; font-family: monospace;">.{{ $sub->file_extension }}</span>
                                        @elseif($sub->file_extension === 'ZIP')
                                            <span class="badge badge-warning" style="font-size: 0.65rem; font-family: monospace;">ZIP</span>
                                        @elseif($sub->file_extension === 'PDF')
                                            <span class="badge badge-danger" style="font-size: 0.65rem; font-family: monospace;">PDF</span>
                                        @else
                                            <span class="badge badge-neutral" style="font-size: 0.65rem; font-family: monospace;">{{ $sub->file_extension }}</span>
                                        @endif

                                        <div>
                                            <div class="file-name-text" style="font-family: monospace; font-size: var(--font-size-xs); word-break: break-all;">
                                                {{ $sub->file_name }}
                                            </div>
                                            <div style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;">
                                                Size: {{ $sub->formatted_file_size }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Action -->
                                <td style="text-align: right;">
                                    <a href="{{ route('submissions.download', $sub->id) }}" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: var(--font-size-xs); text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;" title="Download Student Solution">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        <span>Download</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($submissions->hasPages())
                <div style="margin-top: var(--space-4); display: flex; justify-content: flex-end;">
                    {{ $submissions->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

<!-- Real-time Filter Script (Pure Vanilla JS - Zero CDN) -->
<script>
    async function refreshSubmissionsDashboard() {
        try {
            const response = await fetch(window.location.href, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                throw new Error(`Dashboard refresh failed with status ${response.status}`);
            }

            const html = await response.text();
            const refreshedDashboard = new DOMParser()
                .parseFromString(html, 'text/html')
                .getElementById('submissionDashboard');
            const currentDashboard = document.getElementById('submissionDashboard');

            if (!refreshedDashboard || !currentDashboard) {
                throw new Error('Dashboard refresh response did not contain the submissions dashboard.');
            }

            const searchInput = document.getElementById('submissionSearchInput');
            const searchQuery = searchInput ? searchInput.value : '';
            currentDashboard.innerHTML = refreshedDashboard.innerHTML;

            const refreshedSearchInput = document.getElementById('submissionSearchInput');
            if (refreshedSearchInput) {
                refreshedSearchInput.value = searchQuery;
                filterSubmissionsTable();
            }
        } catch (error) {
            console.error('Unable to refresh the submissions dashboard.', error);
        }
    }

    window.setInterval(refreshSubmissionsDashboard, 15000);

    function filterSubmissionsTable() {
        const searchInput = document.getElementById('submissionSearchInput');
        if (!searchInput) return;

        const query = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.submission-row');

        rows.forEach(row => {
            const studentName = row.querySelector('.student-name-text') ? row.querySelector('.student-name-text').innerText.toLowerCase() : '';
            const studentId = row.querySelector('.student-id-text') ? row.querySelector('.student-id-text').innerText.toLowerCase() : '';
            const taskTitle = row.querySelector('.task-title-text') ? row.querySelector('.task-title-text').innerText.toLowerCase() : '';
            const clientIp = row.querySelector('.client-ip-text') ? row.querySelector('.client-ip-text').innerText.toLowerCase() : '';
            const fileName = row.querySelector('.file-name-text') ? row.querySelector('.file-name-text').innerText.toLowerCase() : '';

            const matches = studentName.includes(query) ||
                            studentId.includes(query) ||
                            taskTitle.includes(query) ||
                            clientIp.includes(query) ||
                            fileName.includes(query);

            if (matches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
