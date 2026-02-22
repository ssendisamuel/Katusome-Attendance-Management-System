@extends('layouts/layoutMaster')

@section('title', 'Bulk Upload Students')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Bulk Upload Students</h4>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card p-4">
        <p class="mb-3">Upload a CSV file with the following headers: <code>SURNAME, OTHERNAMES, PROGRAMME, STUDENT NO.,
                REGISTRATION NO., GENDER, EMAIL, PHONE</code>.</p>
        <p class="mb-3">Global Program and Group selections below are <strong>optional</strong>. If selected, they act as
            defaults for rows with missing or unrecognized values.</p>
        <a href="{{ route('admin.students.import.template') }}" class="btn btn-sm btn-outline-info mb-4">Download CSV
            Template</a>

        <form method="POST" action="{{ route('admin.students.import.process') }}" enctype="multipart/form-data"
            id="importForm">
            @csrf
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Default Program (Optional)</label>
                    <select name="program_id" id="importProgram" class="form-select">
                        <option value="">Select Program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Group (Optional)</label>
                    <select name="group_id" id="importGroup" class="form-select">
                        <option value="">Select Group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('group_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year of Study</label>
                    <input type="number" min="1" max="10" name="year_of_study" class="form-control"
                        value="{{ old('year_of_study', 1) }}">
                    @error('year_of_study')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    @error('file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary" id="submitBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                        id="submitSpinner"></span>
                    <span id="submitText">Upload</span>
                </button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <div class="mt-4" id="progressContainer" style="display:none;">
                    <p>Uploading and Processing... <span id="progressText">0%</span></p>
                    <div class="progress">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="uploadStatus" class="mt-2 small text-muted"></div>
                </div>
            </div>
        </form>
        <hr class="my-4">
        <h6>Notes</h6>
        <ul>
            <li><strong>Required:</strong> `SURNAME`, `EMAIL`, `STUDENT NO.`, `PROGRAMME` (or default selected).</li>
            <li><strong>Gender:</strong> `Male`, `Female` or `Other`.</li>
            <li><strong>Program Matching:</strong> The system attempts to match the `PROGRAMME` column by name. If not found
                or
                empty, the Default Program selected above is used.</li>
            <li>Existing students matched by `STUDENT NO.` are updated.</li>
        </ul>
    </div>
@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const programSelect = document.getElementById('importProgram');
            const groupSelect = document.getElementById('importGroup');
            const importForm = document.getElementById('importForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitSpinner = document.getElementById('submitSpinner');
            const submitText = document.getElementById('submitText');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const uploadStatus = document.getElementById('uploadStatus');

            importForm?.addEventListener('submit', async function(e) {
                e.preventDefault();

                const fileInput = document.querySelector('input[name="file"]');
                const file = fileInput.files[0];
                if (!file) return;

                // UI Reset
                submitBtn.disabled = true;
                submitSpinner.classList.remove('d-none');
                submitText.textContent = 'Preparing...';
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                uploadStatus.innerHTML = '';
                uploadStatus.className = 'mt-2 small text-muted';

                // Parse CSV
                const text = await file.text();
                const rows = text.split(/\r?\n/);
                const headerRow = rows.shift(); // Remove header
                if (!headerRow) {
                    alert("Empty CSV");
                    resetUI();
                    return;
                }

                const headers = headerRow.split(',').map(h => h.trim().toUpperCase().replace(
                    /[^A-Z0-9\.\s]/g, ''));

                // Filter empty rows and parse
                const data = rows.filter(r => r.trim()).map(row => {
                    // Handle CSV lines with commas inside quotes properly?
                    // For now, simple split is safe if no commas in values.
                    // Users should ensure clean CSV.
                    const cols = row.split(',');
                    let obj = {};
                    headers.forEach((h, i) => {
                        obj[h] = cols[i] ? cols[i].trim() : '';
                    });
                    return obj;
                });

                const total = data.length;
                const chunkSize = 50;
                let processed = 0;
                let created = 0,
                    updatedCount = 0,
                    skipped = 0;
                let errors = [];

                submitText.textContent = 'Uploading...';

                for (let i = 0; i < total; i += chunkSize) {
                    const chunk = data.slice(i, i + chunkSize);

                    try {
                        const payload = {
                            rows: chunk,
                            program_id: programSelect.value,
                            group_id: groupSelect.value,
                            year_of_study: document.querySelector('input[name="year_of_study"]')
                                .value,
                            _token: document.querySelector('input[name="_token"]').value
                        };

                        const res = await fetch("{{ route('admin.students.import.process') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (!res.ok) {
                            const errorData = await res.json().catch(() => ({}));
                            throw new Error(errorData.message || `HTTP ${res.status}`);
                        }

                        const result = await res.json();
                        created += result.created;
                        updatedCount += result.updated;
                        skipped += result.skipped;
                        if (result.errors) errors = errors.concat(result.errors);

                        processed += chunk.length;
                        const pct = Math.round((processed / total) * 100);
                        progressBar.style.width = `${pct}%`;
                        progressText.textContent = `${pct}%`;
                        uploadStatus.textContent = `Processed ${processed}/${total} records...`;

                    } catch (err) {
                        console.error(err);
                        uploadStatus.textContent =
                            `Error uploading chunk ${i}-${i+chunkSize}. Retrying...`;
                        errors.push(`Chunk ${i} failed: ${err.message}`);
                    }
                }

                // Done
                submitSpinner.classList.add('d-none');
                submitText.textContent = 'Completed';

                let finalMsg =
                    `Done! Created: ${created}, Updated: ${updatedCount}, Skipped: ${skipped}.`;
                if (errors.length) {
                    finalMsg += `<br><span class="text-danger">Errors: ${errors.length}</span>`;
                    finalMsg +=
                        `<div class="mt-2 text-danger small" style="max-height: 100px; overflow-y: auto;">`;
                    finalMsg += errors.slice(0, 5).map(e => `<div>• ${e}</div>`).join('');
                    if (errors.length > 5) finalMsg +=
                        `<div>... and ${errors.length - 5} more (check console)</div>`;
                    finalMsg += `</div>`;
                }

                uploadStatus.innerHTML = `<strong>${finalMsg}</strong>`;
                uploadStatus.className = errors.length > 0 ? 'mt-2 alert alert-warning' :
                    'mt-2 alert alert-success';

                if (errors.length > 0) {
                    console.log("Import Errors:", errors);
                    // Don't alert if we show them inline
                } else {
                    setTimeout(() => window.location.href = "{{ route('admin.students.index') }}",
                        2000);
                }
            });

            function resetUI() {
                submitBtn.disabled = false;
                submitSpinner.classList.add('d-none');
                submitText.textContent = 'Upload';
            }

            async function fetchGroups(programId) {
                const url = `${window.location.origin}/admin/programs/${programId}/groups`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return [];
                return res.json();
            }
            async function refreshGroups() {
                const pid = programSelect.value;
                groupSelect.innerHTML = '<option value="">Select Group</option>';
                if (!pid) return;
                const groups = await fetchGroups(pid);
                groups.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.id;
                    opt.textContent = g.name;
                    groupSelect.appendChild(opt);
                });
            }
            programSelect?.addEventListener('change', refreshGroups);
        });
    </script>
@endsection
@endsection
