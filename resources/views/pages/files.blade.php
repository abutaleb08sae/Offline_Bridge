@extends('layouts.app')

@section('title', 'Files Management')

@section('content')
<!-- TODO: Connect local FileStorage service & disk upload handler -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- File Upload Dropzone Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Upload Local Files</h3>
                <p class="card-subtitle">Distribute lab assignments (.pkt, .pdf) or collect student project files</p>
            </div>
        </div>

        <div class="upload-dropzone" onclick="showToast('Upload File', 'File upload dialog opened (UI Demo)', 'info')">
            <svg class="upload-dropzone-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">
                Drag & drop files here or click to browse
            </h4>
            <p style="font-size: var(--font-size-xs); color: var(--text-muted);">
                Supports .pkt, .pka, .pdf, .cpp, .zip (Max size: 100MB per file)
            </p>
        </div>
    </div>

    <!-- Managed Files Table Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Master Local Files Library</h3>
                <p class="card-subtitle">Stored on Master Host offline drive (D:\OfflineBridgeData)</p>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="tab-container">
            <button class="tab-btn active" onclick="filterFiles('ALL', this)">All Files (4)</button>
            <button class="tab-btn" onclick="filterFiles('Assignments', this)">Assignments (1)</button>
            <button class="tab-btn" onclick="filterFiles('Templates', this)">Templates (1)</button>
            <button class="tab-btn" onclick="filterFiles('Student Uploads', this)">Student Uploads (2)</button>
        </div>

        <!-- Files Table -->
        <div class="table-container">
            <table class="table" id="filesTable">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Category</th>
                        <th>File Type</th>
                        <th>File Size</th>
                        <th>Uploaded By</th>
                        <th>Date Uploaded</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $f)
                        <tr data-category="{{ $f['category'] }}">
                            <td style="font-weight: 600;">{{ $f['name'] }}</td>
                            <td><span class="badge badge-neutral">{{ $f['category'] }}</span></td>
                            <td><span class="badge badge-info">{{ $f['type'] }}</span></td>
                            <td style="font-size: var(--font-size-xs); font-family: monospace;">{{ $f['size'] }}</td>
                            <td style="font-size: var(--font-size-xs);">{{ $f['uploaded_by'] }}</td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $f['date'] }}</td>
                            <td style="text-align: right;">
                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Download Initiated', 'Downloading {{ $f['name'] }}', 'success')">
                                    Download
                                </button>
                                <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Delete File', 'Removed {{ $f['name'] }} (UI Demo)', 'warning')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    function filterFiles(category, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('#filesTable tbody tr');
        rows.forEach(r => {
            if (category === 'ALL' || r.getAttribute('data-category') === category) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }
</script>
@endsection
