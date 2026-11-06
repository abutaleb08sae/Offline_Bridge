@extends('layouts.app')

@section('title', 'Lab Tasks & File Distribution')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Success / Error Session Feedback Alerts -->
    @if(session('success'))
        <div class="card" style="background-color: var(--color-success-bg); border-color: rgba(16, 185, 129, 0.4); padding: var(--space-4);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);">
                <div style="display: flex; align-items: center; gap: var(--space-3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-success-text); flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span style="font-weight: 600; color: var(--color-success-text); font-size: var(--font-size-sm);">
                        {{ session('success') }}
                    </span>
                </div>
                <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.5rem; font-size: var(--font-size-xs);" onclick="this.closest('.card').remove()">
                    Dismiss
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="card" style="background-color: var(--color-danger-bg); border-color: rgba(239, 68, 68, 0.4); padding: var(--space-4);">
            <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-danger-text); flex-shrink: 0; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div style="flex: 1;">
                    <strong style="color: var(--color-danger-text); font-size: var(--font-size-sm); display: block; margin-bottom: var(--space-2);">
                        Upload Failed - Please correct the following:
                    </strong>
                    <ul style="margin: 0; padding-left: var(--space-4); color: var(--color-danger-text); font-size: var(--font-size-xs); line-height: 1.5;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->check() && auth()->user()->role === 'admin')
    <!-- Instructor Upload Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Upload New Lab Task (.pkt / .pkz)</h3>
                <p class="card-subtitle">
                    Distribute Cisco Packet Tracer topologies, network assignments, or instructional guides to offline LAN workstations
                </p>
            </div>
            <span class="badge badge-info">Instructor Module</span>
        </div>

        <form action="{{ route('labs.store') }}" method="POST" enctype="multipart/form-data" id="labUploadForm" onsubmit="return handleFormSubmit(this)">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-4);">
                <!-- Task Title -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="taskTitle">
                        Task Title <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="text" name="title" id="taskTitle" class="form-input" placeholder="e.g. Lab 04: Multiuser Cisco Packet Tracer OSPF Peering" required value="{{ old('title') }}">
                </div>

                <!-- Description -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="taskDescription">
                        Lab Instructions / Description <span style="font-weight: 400; color: var(--text-muted);">(Optional)</span>
                    </label>
                    <textarea name="description" id="taskDescription" class="form-textarea" rows="2" placeholder="Brief lab objectives or connection parameters for students (e.g. Connect Router R1 to Master Host 192.168.1.100 on TCP Port 38000)...">{{ old('description') }}</textarea>
                </div>

                <!-- Drag & Drop File Upload Area -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">
                        Lab File (.pkt, .pkz, .zip, .pdf) <span style="color: var(--color-danger);">*</span>
                    </label>

                    <input type="file" name="lab_file" id="labFileInput" accept=".pkt,.pkz,.zip,.pdf" required style="display: none;" onchange="handleFilePicked(this)">

                    <div class="upload-dropzone" id="dropzoneBox" onclick="document.getElementById('labFileInput').click()" style="cursor: pointer; text-align: center; border: 2px dashed var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6); transition: all 0.2s ease;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="upload-dropzone-icon" style="width: 44px; height: 44px; margin: 0 auto var(--space-2); color: var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />
                        </svg>

                        <div id="dropzonePrompt">
                            <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">
                                Click to choose or drag & drop file here
                            </h4>
                            <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin-bottom: var(--space-2);">
                                Supported formats: <strong style="color: var(--primary);">.pkt</strong>, <strong style="color: var(--primary);">.pkz</strong>, <strong>.zip</strong>, <strong>.pdf</strong> (Max file size: 50MB)
                            </p>
                            <span class="badge badge-neutral" style="font-size: 0.7rem;">
                                Binary / Octet-Stream Verified for Offline LAN
                            </span>
                        </div>

                        <!-- Selected File Preview Badge -->
                        <div id="fileSelectedPreview" style="display: none; margin-top: var(--space-3); padding: var(--space-3); background: var(--bg-surface-hover); border-radius: var(--radius-md); border: 1px solid var(--border-color); align-items: center; justify-content: center; gap: var(--space-3);">
                            <span class="badge badge-success" id="fileExtBadge">PKT</span>
                            <strong id="fileNameDisplay" style="font-family: monospace; font-size: var(--font-size-sm); color: var(--text-main); word-break: break-all;">-</strong>
                            <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;" id="fileSizeDisplay">-</span>
                            <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: var(--font-size-xs);" onclick="event.stopPropagation(); resetFileChoice();">
                                Change
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Action Buttons with Loading State -->
            <div style="display: flex; justify-content: flex-end; gap: var(--space-3); margin-top: var(--space-5); border-top: 1px solid var(--border-color); padding-top: var(--space-4);">
                <button type="button" class="btn btn-secondary" onclick="resetEntireForm()">
                    Reset
                </button>
                <button type="submit" id="submitUploadBtn" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: var(--space-2); min-width: 220px; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" id="btnUploadIcon" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span id="btnUploadText">Upload Lab Task (.pkt)</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Master Local Files Library / Lab Tasks List -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Master Local Files Library</h3>
                <p class="card-subtitle">
                    Stored on Master Host local drive (<code style="font-size: var(--font-size-xs);">storage/app/public/labs/</code>) - Accessible to offline client workstations
                </p>
            </div>
            <span class="badge badge-success">
                {{ $labTasks->count() }} {{ \Illuminate\Support\Str::plural('Task', $labTasks->count()) }} Available
            </span>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-bar">
            <div class="search-input-wrapper" style="flex: 1;">
                <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" id="taskSearchInput" class="form-input" placeholder="Search lab tasks by title or filename..." onkeyup="filterLabTasks()">
            </div>
        </div>

        <!-- Lab Tasks Table -->
        @if($labTasks->isEmpty())
            <div style="text-align: center; padding: var(--space-8) var(--space-4); background-color: var(--bg-surface-hover); border-radius: var(--radius-lg); border: 1px dashed var(--border-color);">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 48px; height: 48px; margin: 0 auto var(--space-3); color: var(--text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
                <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-1);">
                    No Lab Tasks Uploaded Yet
                </h4>
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); max-width: 480px; margin: 0 auto var(--space-4);">
                        Upload a Cisco Packet Tracer (.pkt) activity file or lab assignment sheet above to share with offline student PCs over the local Wi-Fi subnet.
                    </p>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('taskTitle').focus()">
                        Upload First Lab Task
                    </button>
                @else
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); max-width: 480px; margin: 0 auto var(--space-4);">
                        No teacher-published files are available yet.
                    </p>
                @endif
            </div>
        @else
            <div class="table-container">
                <table class="table" id="labTasksTable">
                    <thead>
                        <tr>
                            @if(auth()->check() && auth()->user()->role === 'admin')
                                <th style="width: 40px;">#</th>
                                <th>Task Title & Instructions</th>
                                <th>File Name & Type</th>
                                <th>Size</th>
                            @else
                                <th>Title</th>
                                <th>File Name</th>
                            @endif
                            <th>Uploaded At</th>
                            <th style="text-align: right; width: 180px;">{{ auth()->check() && auth()->user()->role === 'admin' ? 'Actions' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labTasks as $index => $task)
                            @php
                                $ext = strtolower(pathinfo($task->file_name, PATHINFO_EXTENSION));
                            @endphp
                            <tr class="lab-task-row">
                                @if(auth()->check() && auth()->user()->role === 'admin')
                                    <td style="color: var(--text-muted); font-size: var(--font-size-xs);">
                                        {{ $index + 1 }}
                                    </td>
                                @endif
                                <td>
                                    <div class="task-title-text" style="font-weight: 700; color: var(--text-main); margin-bottom: 0.15rem;">
                                        {{ $task->title }}
                                    </div>
                                    @if(auth()->check() && auth()->user()->role === 'admin')
                                        @if($task->description)
                                            <div style="font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.35; max-width: 400px; margin-bottom: 0.25rem;">
                                                {{ $task->description }}
                                            </div>
                                        @endif
                                        @if(isset($task->submissions_count) && $task->submissions_count > 0)
                                            <span class="badge badge-success" style="font-size: 0.65rem;">
                                                {{ $task->submissions_count }} {{ \Illuminate\Support\Str::plural('Submission', $task->submissions_count) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        @if(in_array($ext, ['pkt', 'pkz']))
                                            <span class="badge badge-success" style="font-family: monospace; font-size: 0.65rem;">.{{ strtoupper($ext) }}</span>
                                        @elseif($ext === 'pdf')
                                            <span class="badge badge-danger" style="font-family: monospace; font-size: 0.65rem;">PDF</span>
                                        @elseif($ext === 'zip')
                                            <span class="badge badge-warning" style="font-family: monospace; font-size: 0.65rem;">ZIP</span>
                                        @else
                                            <span class="badge badge-neutral" style="font-family: monospace; font-size: 0.65rem;">{{ strtoupper($ext) }}</span>
                                        @endif
                                        <span class="task-file-text" style="font-family: monospace; font-size: var(--font-size-xs); word-break: break-all;">
                                            {{ $task->file_name }}
                                        </span>
                                    </div>
                                </td>
                                @if(auth()->check() && auth()->user()->role === 'admin')
                                    <td style="font-size: var(--font-size-xs); font-family: monospace; color: var(--text-muted);">
                                        {{ $task->formatted_file_size }}
                                    </td>
                                @endif
                                <td style="font-size: var(--font-size-xs); color: var(--text-muted);">
                                    <div>{{ $task->created_at->format('M d, Y') }}</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $task->created_at->format('h:i A') }} ({{ $task->created_at->diffForHumans() }})</div>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: var(--space-2);">
                                        <!-- Download Button -->
                                        <a href="{{ route('labs.download', $task->id) }}" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: var(--font-size-xs); text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;" title="Download {{ $task->file_name }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            <span>Download</span>
                                        </a>

                                        @if(auth()->check() && auth()->user()->role === 'admin')
                                        <!-- Delete Button Form -->
                                        <form action="{{ route('labs.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete \'{{ addslashes($task->title) }}\'?\n\n@if(isset($task->submissions_count) && $task->submissions_count > 0)WARNING: This task has {{ $task->submissions_count }} student submission(s) which will also be removed from storage!\n\n@endif This will remove the file from Master storage.');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.55rem; font-size: var(--font-size-xs); display: inline-flex; align-items: center; gap: 0.25rem;" title="Delete Task">
                                                <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<!-- Interactive Client-side Script (Zero External Dependencies) -->
@if(auth()->check() && auth()->user()->role === 'admin')
<script>
    // Drag & Drop handlers for dropzone
    const dropzoneBox = document.getElementById('dropzoneBox');
    const labFileInput = document.getElementById('labFileInput');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzoneBox.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneBox.style.borderColor = 'var(--primary)';
            dropzoneBox.style.backgroundColor = 'var(--primary-light)';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzoneBox.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneBox.style.borderColor = 'var(--border-color)';
            dropzoneBox.style.backgroundColor = 'transparent';
        }, false);
    });

    dropzoneBox.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            labFileInput.files = files;
            handleFilePicked(labFileInput);
        }
    }, false);

    // File selected display preview
    function handleFilePicked(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];

        // Format file size
        let sizeStr = '';
        if (file.size >= 1048576) {
            sizeStr = (file.size / 1048576).toFixed(2) + ' MB';
        } else if (file.size >= 1024) {
            sizeStr = (file.size / 1024).toFixed(2) + ' KB';
        } else {
            sizeStr = file.size + ' B';
        }

        // Get extension
        const parts = file.name.split('.');
        const ext = parts.length > 1 ? parts.pop().toUpperCase() : 'FILE';

        document.getElementById('fileNameDisplay').textContent = file.name;
        document.getElementById('fileSizeDisplay').textContent = '(' + sizeStr + ')';
        document.getElementById('fileExtBadge').textContent = ext;

        // Auto-fill Title if empty
        const titleInput = document.getElementById('taskTitle');
        if (!titleInput.value.trim()) {
            const rawName = parts.join('.');
            titleInput.value = rawName.replace(/[_-]+/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        document.getElementById('dropzonePrompt').style.display = 'none';
        document.getElementById('fileSelectedPreview').style.display = 'inline-flex';
    }

    function resetFileChoice() {
        labFileInput.value = '';
        document.getElementById('dropzonePrompt').style.display = 'block';
        document.getElementById('fileSelectedPreview').style.display = 'none';
    }

    function resetEntireForm() {
        document.getElementById('labUploadForm').reset();
        resetFileChoice();
    }

    // Submit Loading State
    function handleFormSubmit(form) {
        const btn = document.getElementById('submitUploadBtn');
        const btnText = document.getElementById('btnUploadText');
        const icon = document.getElementById('btnUploadIcon');

        btn.disabled = true;
        btn.style.opacity = '0.75';
        btn.style.cursor = 'not-allowed';
        btnText.textContent = 'Uploading to Master PC... Please wait';

        return true;
    }

    // Search & Filter Lab Tasks
    function filterLabTasks() {
        const query = document.getElementById('taskSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.lab-task-row');

        rows.forEach(row => {
            const title = row.querySelector('.task-title-text') ? row.querySelector('.task-title-text').innerText.toLowerCase() : '';
            const file = row.querySelector('.task-file-text') ? row.querySelector('.task-file-text').innerText.toLowerCase() : '';

            if (title.includes(query) || file.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endif
@endsection
