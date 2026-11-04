<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Lab Solution - OfflineBridge Client Portal</title>
    
    <!-- 100% Local Offline Stylesheet (Zero-CDN Architecture) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('offlinebridge_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body style="min-height: 100vh; background-color: var(--bg-body); display: flex; flex-direction: column; justify-content: space-between; padding: var(--space-4);">

    <!-- Top Minimal Navigation Bar -->
    <header style="max-width: 720px; width: 100%; margin: 0 auto var(--space-6); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: var(--space-3);">
            <div style="width: 38px; height: 38px; background-color: var(--primary); color: #ffffff; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem;">
                OB
            </div>
            <div>
                <h1 style="font-size: var(--font-size-lg); font-weight: 700; color: var(--text-main); margin: 0; line-height: 1.2;">
                    OfflineBridge
                </h1>
                <span style="font-size: var(--font-size-xs); color: var(--text-muted);">Student Client Workstation</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: var(--space-2);">
            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="display: inline-block; width: 6px; height: 6px; background-color: #10b981; border-radius: 50%;"></span>
                Isolated LAN
            </span>
            <span class="badge badge-neutral" style="font-family: monospace; font-size: 0.75rem;">
                Detected IP: {{ $clientIp }}
            </span>
        </div>
    </header>

    <!-- Main Content Container -->
    <main style="max-width: 720px; width: 100%; margin: 0 auto; flex: 1;">

        <!-- Success Alert Banner -->
        @if(session('success'))
            <div class="card" style="background-color: var(--color-success-bg); border-color: rgba(16, 185, 129, 0.4); padding: var(--space-4); margin-bottom: var(--space-6);">
                <div style="display: flex; align-items: center; gap: var(--space-3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; color: var(--color-success-text); flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div style="flex: 1;">
                        <strong style="color: var(--color-success-text); font-size: var(--font-size-sm); display: block;">
                            {{ session('success') }}
                        </strong>
                        <span style="color: var(--color-success-text); font-size: var(--font-size-xs);">
                            Your solution has been securely received by the Master Host offline storage.
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Validation Error Alert Banner -->
        @if($errors->any())
            <div class="card" style="background-color: var(--color-danger-bg); border-color: rgba(239, 68, 68, 0.4); padding: var(--space-4); margin-bottom: var(--space-6);">
                <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-danger-text); flex-shrink: 0; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <strong style="color: var(--color-danger-text); font-size: var(--font-size-sm); display: block; margin-bottom: var(--space-1);">
                            Please resolve the following submission issues:
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

        <!-- Submission Form Card -->
        <div class="card" style="padding: var(--space-6);">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-4); margin-bottom: var(--space-6);">
                <h2 style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-1);">
                    Lab Solution Submission Portal
                </h2>
                <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0; line-height: 1.5;">
                    Upload your completed Cisco Packet Tracer activity topology (<strong style="color: var(--primary);">.pkt</strong>, <strong style="color: var(--primary);">.pkz</strong>) or ZIP archive to Master PC over the offline LAN.
                </p>
            </div>

            <form action="{{ route('submissions.store') }}" method="POST" enctype="multipart/form-data" id="studentSubmissionForm" onsubmit="return handleFormSubmit(this)">
                @csrf

                <div style="display: flex; flex-direction: column; gap: var(--space-4);">

                    <!-- Lab Task Selection -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="lab_task_id">
                            Assigned Lab Task <span style="color: var(--color-danger);">*</span>
                        </label>
                        @if($labTasks->isEmpty())
                            <div style="padding: var(--space-3); background-color: var(--color-warning-bg); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-md); font-size: var(--font-size-xs); color: var(--color-warning-text);">
                                No active lab tasks are currently published by the instructor on Master PC.
                            </div>
                        @else
                            <select name="lab_task_id" id="lab_task_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Your Lab Task --</option>
                                @foreach($labTasks as $task)
                                    <option value="{{ $task->id }}" {{ old('lab_task_id') == $task->id ? 'selected' : '' }}>
                                        {{ $task->title }} ({{ $task->file_name }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Student Name & Student ID Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="student_name">
                                Full Name <span style="color: var(--color-danger);">*</span>
                            </label>
                            <input type="text" name="student_name" id="student_name" class="form-input" placeholder="e.g. John Doe" required value="{{ old('student_name') }}">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="student_id">
                                Student ID / Roll No. <span style="color: var(--color-danger);">*</span>
                            </label>
                            <input type="text" name="student_id" id="student_id" class="form-input" placeholder="e.g. 2026-CS-042" required value="{{ old('student_id') }}">
                        </div>
                    </div>

                    <!-- Solution File Dropzone -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">
                            Solution File (.pkt, .pkz, .zip, .pdf) <span style="color: var(--color-danger);">*</span>
                        </label>

                        <input type="file" name="submission_file" id="submissionFileInput" accept=".pkt,.pkz,.zip,.pdf" required style="display: none;" onchange="handleFileSelected(this)">

                        <div class="upload-dropzone" id="submissionDropzone" onclick="document.getElementById('submissionFileInput').click()" style="cursor: pointer; text-align: center; border: 2px dashed var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6); transition: all 0.2s ease;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="upload-dropzone-icon" style="width: 40px; height: 40px; margin: 0 auto var(--space-2); color: var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />
                            </svg>

                            <div id="filePromptText">
                                <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">
                                    Click to browse or drop your solution file
                                </h4>
                                <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">
                                    Accepted: <strong style="color: var(--primary);">.pkt</strong>, <strong style="color: var(--primary);">.pkz</strong>, <strong>.zip</strong>, <strong>.pdf</strong> (Max: 50MB)
                                </p>
                            </div>

                            <!-- Selected File Badge -->
                            <div id="filePreviewBadge" style="display: none; margin-top: var(--space-2); padding: var(--space-3); background: var(--bg-surface-hover); border-radius: var(--radius-md); border: 1px solid var(--border-color); align-items: center; justify-content: center; gap: var(--space-3);">
                                <span class="badge badge-success" id="extBadgeText">PKT</span>
                                <strong id="fileNameText" style="font-family: monospace; font-size: var(--font-size-sm); color: var(--text-main); word-break: break-all;">-</strong>
                                <span id="fileSizeText" style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;">-</span>
                                <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: var(--font-size-xs);" onclick="event.stopPropagation(); resetSelectedFile();">
                                    Change
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Client IP Note -->
                    <div style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-xs); color: var(--text-muted);">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; color: var(--primary); flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <span>Your client IP address (<strong style="font-family: monospace; color: var(--text-main);">{{ $clientIp }}</strong>) is verified and automatically tagged to your submission for academic integrity.</span>
                    </div>

                    <!-- Submit Button with Loading State -->
                    <div style="margin-top: var(--space-4);">
                        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: var(--space-3) var(--space-4); font-size: var(--font-size-base); font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);" {{ $labTasks->isEmpty() ? 'disabled' : '' }}>
                            <!-- Paper Plane Inline SVG Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" id="submitIcon" style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <span id="submitBtnText">Submit Lab Solution</span>
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- Need Help / Download Lab Task Link -->
        <div style="text-align: center; margin-top: var(--space-6);">
            <a href="{{ route('labs.index') }}" style="color: var(--primary); font-size: var(--font-size-xs); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Need assignment topologies? Download lab task files here</span>
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer style="max-width: 720px; width: 100%; margin: var(--space-8) auto 0; text-align: center; font-size: var(--font-size-xs); color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: var(--space-4);">
        OfflineBridge LAN Lab System &bull; Master Host Wi-Fi Gateway &bull; Zero External Dependencies
    </footer>

    <!-- Interactive Client Validation & Dropzone Script (Vanilla JS) -->
    <script>
        const dropzone = document.getElementById('submissionDropzone');
        const fileInput = document.getElementById('submissionFileInput');

        ['dragenter', 'dragover'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = 'var(--primary)';
                dropzone.style.backgroundColor = 'var(--primary-light)';
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = 'var(--border-color)';
                dropzone.style.backgroundColor = 'transparent';
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                fileInput.files = files;
                handleFileSelected(fileInput);
            }
        });

        function handleFileSelected(input) {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];

            let sizeStr = '';
            if (file.size >= 1048576) {
                sizeStr = (file.size / 1048576).toFixed(2) + ' MB';
            } else if (file.size >= 1024) {
                sizeStr = (file.size / 1024).toFixed(2) + ' KB';
            } else {
                sizeStr = file.size + ' B';
            }

            const parts = file.name.split('.');
            const ext = parts.length > 1 ? parts.pop().toUpperCase() : 'FILE';

            document.getElementById('fileNameText').textContent = file.name;
            document.getElementById('fileSizeText').textContent = '(' + sizeStr + ')';
            document.getElementById('extBadgeText').textContent = ext;

            document.getElementById('filePromptText').style.display = 'none';
            document.getElementById('filePreviewBadge').style.display = 'inline-flex';
        }

        function resetSelectedFile() {
            fileInput.value = '';
            document.getElementById('filePromptText').style.display = 'block';
            document.getElementById('filePreviewBadge').style.display = 'none';
        }

        function handleFormSubmit(form) {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('submitBtnText');

            btn.disabled = true;
            btn.style.opacity = '0.75';
            btn.style.cursor = 'not-allowed';
            text.textContent = 'Uploading Solution to Master PC... Please wait';

            return true;
        }
    </script>
</body>
</html>

