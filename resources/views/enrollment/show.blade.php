@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Enroll in Semester')

@section('content')
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Semester Enrollment</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="ri-information-line me-2"></i>Enrollment Required</h5>
                        <p class="mb-0">Please enroll in <strong>{{ $activeSemester->display_name }}</strong> to access
                            your
                            dashboard and mark attendance.</p>
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Student Identity</h6>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>Name:</strong> {{ $student->name }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Email:</strong> {{ optional($student->user)->email }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Student No.:</strong> <span
                                        class="badge bg-primary">{{ $student->student_no }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Registration No.:</strong> <span
                                        class="badge bg-info">{{ $student->reg_no ?? 'N/A' }}</span>
                                </div>
                                @php
                                    $dispProgram = $enrollment?->program ?? $student->program;
                                    $dispFaculty = $dispProgram?->faculty;
                                    $dispCampusName =
                                        $enrollment?->campus?->name ??
                                        (optional($dispFaculty?->campuses?->first())->name ?? 'Main Campus');
                                @endphp
                                @if ($dispFaculty)
                                    <div class="col-md-6 mb-2">
                                        <strong>Faculty:</strong> {{ $dispFaculty->name }}
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Campus:</strong> {{ $dispCampusName }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (isset($enrollment) && $enrollment)
                        <div class="alert alert-success">
                            <h5><i class="ri-checkbox-circle-line me-2"></i>Enrolled</h5>
                            <p class="mb-0">You are already enrolled in
                                <strong>{{ $activeSemester->display_name }}</strong>.
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Academic Year & Semester</label>
                            <input type="text" class="form-control" value="{{ $activeSemester->display_name }}" readonly
                                disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Year of Study</label>
                            <input type="text" class="form-control" value="Year {{ $enrollment->year_of_study }}"
                                readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Program</label>
                            <input type="text" class="form-control" value="{{ optional($enrollment->program)->name }}"
                                readonly disabled>
                        </div>

                        @if ($enrollment->program && $enrollment->program->faculty)
                            <div class="mb-3">
                                <label class="form-label">Faculty</label>
                                <input type="text" class="form-control"
                                    value="{{ $enrollment->program->faculty->name }}" readonly disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Campus</label>
                                <input type="text" class="form-control"
                                    value="{{ $enrollment->campus?->name ?? (optional($enrollment->program->faculty->campuses->first())->name ?? 'Main Campus') }}"
                                    readonly disabled>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Group</label>
                            <input type="text" class="form-control" value="{{ optional($enrollment->group)->name }}"
                                readonly disabled>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary">Go to Dashboard</a>
                            <button type="button" class="btn btn-link"
                                onclick="document.getElementById('enrollmentForm').style.display = 'block'; this.style.display = 'none';">Change
                                Group/Program</button>
                        </div>
                    @endif

                    {{-- Form is always present but hidden if enrolled --}}
                    <form id="enrollmentForm" method="POST" action="{{ route('enrollment.store') }}"
                        style="{{ isset($enrollment) && $enrollment ? 'display:none; margin-top: 20px;' : '' }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Academic Year & Semester</label>
                            <input type="text" class="form-control" value="{{ $activeSemester->display_name }}"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="year_of_study">Year of Study <span
                                    class="text-danger">*</span></label>
                            <select class="form-select @error('year_of_study') is-invalid @enderror" name="year_of_study"
                                id="year_of_study" required>
                                <option value="">Select Year</option>
                                <option value="1"
                                    {{ old('year_of_study', $enrollment->year_of_study ?? $student->year_of_study) == 1 ? 'selected' : '' }}>
                                    Year 1
                                </option>
                                <option value="2"
                                    {{ old('year_of_study', $enrollment->year_of_study ?? $student->year_of_study) == 2 ? 'selected' : '' }}>
                                    Year 2
                                </option>
                                <option value="3"
                                    {{ old('year_of_study', $enrollment->year_of_study ?? $student->year_of_study) == 3 ? 'selected' : '' }}>
                                    Year 3
                                </option>
                                <option value="4"
                                    {{ old('year_of_study', $enrollment->year_of_study ?? $student->year_of_study) == 4 ? 'selected' : '' }}>
                                    Year 4
                                </option>
                            </select>
                            @error('year_of_study')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="program_id">Program <span class="text-danger">*</span></label>
                            <select class="form-select @error('program_id') is-invalid @enderror" name="program_id"
                                id="program_id" required onchange="onProgramChange()">
                                <option value="">Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}"
                                        {{ old('program_id', $enrollment?->program_id ?? $student->program_id) == $program->id ? 'selected' : '' }}>
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">You can change your program if needed for this semester</small>
                            @error('program_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Faculty <small class="text-muted">(auto)</small></label>
                                <input type="text" id="faculty_display" class="form-control"
                                    placeholder="Auto from Program" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="campus_id">Campus <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('campus_id') is-invalid @enderror" name="campus_id"
                                    id="campus_id" required>
                                    <option value="">Select Campus</option>
                                    @foreach ($campuses as $c)
                                        <option value="{{ $c->id }}"
                                            {{ old('campus_id', $enrollment?->campus_id) == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('campus_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="group_id">Group <span class="text-danger">*</span></label>
                            <select class="form-select @error('group_id') is-invalid @enderror" name="group_id"
                                id="group_id" required>
                                <option value="">Select Group</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}"
                                        {{ old('group_id', $enrollment->group_id ?? $student->group_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select your current group for this semester</small>
                            @error('group_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-check-line me-1"></i>
                                {{ isset($enrollment) && $enrollment ? 'Update Enrollment' : 'Enroll Now' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: @json(session('success')),
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });
        </script>
    @endif

    @if (session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Information',
                        text: @json(session('info')),
                    });
                }
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<ul style="text-align: left;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                    });
                }
            });
        </script>
    @endif
@endsection

@section('page-script')
    <script>
        const programMap = @json($programMap);

        function onProgramChange() {
            var pSel = document.getElementById('program_id');
            var pid = pSel.value;
            var info = programMap[pid];
            var fDisp = document.getElementById('faculty_display');
            var cSel = document.getElementById('campus_id');
            var currentVal = cSel.value;

            if (!info) {
                fDisp.value = '';
                // Hide all campuses except placeholder
                cSel.querySelectorAll('option').forEach(o => {
                    if (o.value) o.style.display = 'none';
                });
                return;
            }

            fDisp.value = info.faculty_name;

            var campusIds = info.campuses.map(c => c.id.toString());
            var isCurrentValid = false;

            // Show matching, hide others
            cSel.querySelectorAll('option').forEach(function(o) {
                if (!o.value) {
                    o.style.display = '';
                    return;
                }
                var valid = campusIds.includes(o.value);
                o.style.display = valid ? '' : 'none';
                if (valid && (o.value === currentVal)) {
                    isCurrentValid = true;
                }
            });

            // Sync values
            if (campusIds.length === 1) {
                cSel.value = campusIds[0];
            } else if (!isCurrentValid && currentVal !== "") {
                cSel.value = "";
            }
        }

        // Run on page load to set faculty/campus based on pre-selected program
        document.addEventListener('DOMContentLoaded', function() {
            onProgramChange();
        });
    </script>
@endsection
