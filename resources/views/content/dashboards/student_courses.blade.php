@extends('layouts/layoutMaster')

@section('title', 'My Courses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Student /</span> My Courses
        </h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerRetakeModal">
            <i class="ri-add-line me-1"></i> Register Retake/Extra
        </button>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Courses for {{ $enrollment->academicSemester->display_name }}</h5>
            <small class="text-muted">{{ $enrollment->program->name }} - Year {{ $enrollment->year_of_study }}</small>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>C.U</th>
                        <th>Type</th>
                        <th>Lecturers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($activeCourses as $course)
                        <tr>
                            <td><span class="fw-bold">{{ $course->code }}</span></td>
                            <td>{{ $course->name }}</td>
                            <td>
                                {{--
                                    Credit Units logic:
                                    1. Check if it's a program course (has programs relationship loaded first).
                                    2. If retake, it might not have 'programs' pivot for *this* student's current program/year context directly attached in the collection
                                       if it came via extraCourses().
                                    3. Fallback to course default or 3.
                                --}}
                                @php
                                    $pivotCu = null;
                                    // If it has a program pivot (standard course)
                                    if ($course->programs->isNotEmpty()) {
                                        $pivotCu = $course->programs->first()->pivot->credit_units ?? null;
                                    }
                                    echo $pivotCu ?? ($course->credit_units ?? 3);
                                @endphp
                            </td>
                            <td>
                                @php
                                    // Determine Type: Retake vs Standard
                                    // extraCourses() pivot has 'type' (retake/missed/extra)
                                    // programs() pivot has 'course_type' (Core/Elective)

                                    $displayType = 'Core';
                                    $badgeClass = 'bg-label-primary';

                                    // Check if it's a retake registration
if (isset($course->pivot) && isset($course->pivot->type)) {
    $displayType = ucfirst($course->pivot->type); // Retake, Missed, Extra
    $badgeClass = 'bg-label-warning'; // Orange for retakes
}
// Else standard program course
elseif ($course->programs->isNotEmpty()) {
    $pType = $course->programs->first()->pivot->course_type ?? 'Core';
    $displayType = ucfirst($pType);
    $badgeClass = match (strtolower($pType)) {
        'core' => 'bg-label-primary',
        'elective' => 'bg-label-info',
        'audit' => 'bg-label-secondary',
        default => 'bg-label-primary',
                                        };
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }} me-1">{{ $displayType }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 my-3">
                                    @forelse ($course->lecturers->unique('id') as $lecturer)
                                        <span class="badge bg-label-primary">{{ $lecturer->name }}</span>
                                    @empty
                                        <span class="text-muted"><small>Not assigned</small></span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if (isset($course->pivot) &&
                                        isset($course->pivot->type) &&
                                        in_array($course->pivot->type, ['retake', 'missed', 'extra']))
                                    <form action="{{ route('student.courses.retake.destroy', $course->pivot->id) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger delete-record"
                                            data-bs-toggle="tooltip" title="Remove Registration">
                                            <i class="ri-delete-bin-line me-1"></i> Remove
                                        </button>
                                    </form>
                                @elseif (
                                    $course->programs->isNotEmpty() &&
                                        in_array(strtolower($course->programs->first()->pivot->course_type ?? ''), ['elective', 'audit']))
                                    <form action="{{ route('student.courses.drop', $course->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning drop-course"
                                            data-bs-toggle="tooltip" title="Drop Elective">
                                            <i class="ri-close-circle-line me-1"></i> Drop
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No courses found for this semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($droppedCourses->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Dropped / Available Electives</h5>
                <small class="text-muted">Elective or Audit courses you have chosen not to take this semester.</small>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>C.U</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($droppedCourses as $course)
                            <tr>
                                <td><span class="fw-bold">{{ $course->code }}</span></td>
                                <td>{{ $course->name }}</td>
                                <td>{{ $course->programs->first()->pivot->credit_units ?? ($course->credit_units ?? 3) }}
                                </td>
                                <td>
                                    @php
                                        $pType = $course->programs->first()->pivot->course_type ?? 'Elective';
                                        $badgeClass = match (strtolower($pType)) {
                                            'elective' => 'bg-label-info',
                                            'audit' => 'bg-label-secondary',
                                            default => 'bg-label-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} me-1">{{ ucfirst($pType) }}</span>
                                </td>
                                <td>
                                    <!-- We just delete the "dropped" registration to make it active again -->
                                    @php
                                        $dropRegistration = \App\Models\StudentCourseRegistration::where(
                                            'student_id',
                                            $student->id,
                                        )
                                            ->where('course_id', $course->id)
                                            ->where('academic_semester_id', $enrollment->academic_semester_id)
                                            ->where('type', 'dropped')
                                            ->first();
                                    @endphp
                                    @if ($dropRegistration)
                                        <form action="{{ route('student.courses.retake.destroy', $dropRegistration->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-success restore-course"
                                                data-bs-toggle="tooltip" title="Add Course Back">
                                                <i class="ri-add-circle-line me-1"></i> Add Back
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Registration Modal -->
    <div class="modal fade" id="registerRetakeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register for Retake/Missed Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('student.courses.retake') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <small>Use this to register for courses outside your current enrollment (e.g. Retakes).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Program</label>
                            <input type="text" class="form-control" value="{{ $enrollment->program->name }}" readonly
                                disabled>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="yearSelect" class="form-label">Select Year of Study</label>
                                <select id="yearSelect" class="form-select" required>
                                    <option value="">Select Year...</option>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="groupSelect" class="form-label">Select Target Group</label>
                                <select id="groupSelect" name="target_group_id" class="form-select" required>
                                    <option value="">Select Group...</option>
                                    {{-- Groups loaded via AJAX or passed if small --}}
                                </select>
                                <div class="form-text">Select the group you will attend classes with.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="courseSelect" class="form-label">Select Course</label>
                            <select id="courseSelect" name="course_id" class="form-select select2" required disabled>
                                <option value="">First select a Year...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Registration Type</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="typeRetake"
                                        value="retake" checked>
                                    <label class="form-check-label" for="typeRetake">Retake</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="typeMissed"
                                        value="missed">
                                    <label class="form-check-label" for="typeMissed">Missed Course</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="typeExtra"
                                        value="extra">
                                    <label class="form-check-label" for="typeExtra">Extra Load</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Register Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script type="module">
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                dropdownParent: $('#registerRetakeModal'),
                width: '100%'
            });

            // Delete Record Logic (Standard Retakes/Extras)
            $(document).on('click', '.delete-record', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove it!',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Drop Course Logic (Electives/Audits)
            $(document).on('click', '.drop-course', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');

                Swal.fire({
                    title: 'Drop Elective?',
                    text: "This will remove the course from your active dashboard. You can add it back later if needed.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, drop it',
                    customClass: {
                        confirmButton: 'btn btn-warning me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Restore Course Logic (Add back dropped Electives/Audits)
            $(document).on('click', '.restore-course', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');

                Swal.fire({
                    title: 'Restore Course?',
                    text: "This will add the course back to your active list.",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, add it',
                    customClass: {
                        confirmButton: 'btn btn-success me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            const academicSemesterId = {{ $enrollment->academic_semester_id }};

            // Load Groups on modal open (once)
            let groupsLoaded = false;
            $('#registerRetakeModal').on('show.bs.modal', function() {
                if (!groupsLoaded) {
                    $.get("{{ route('student.api.groups') }}", function(data) {
                        let options = '<option value="">Select Group...</option>';
                        data.forEach(function(group) {
                            options += `<option value="${group.id}">${group.name}</option>`;
                        });
                        $('#groupSelect').html(options);
                        groupsLoaded = true;
                    });
                }
            });

            // Load Courses when Year changes
            $('#yearSelect').on('change', function() {
                const year = $(this).val();
                const $courseSelect = $('#courseSelect');

                if (!year) {
                    $courseSelect.html('<option value="">First select a Year...</option>').prop('disabled',
                        true);
                    return;
                }

                $courseSelect.html('<option value="">Loading courses...</option>').prop('disabled', true);

                $.ajax({
                    url: "{{ route('student.api.courses') }}",
                    data: {
                        year: year,
                        semester_id: academicSemesterId
                    },
                    success: function(data) {
                        let options = '<option value="">Select a course...</option>';
                        if (data.length === 0) {
                            options = '<option value="">No courses found for Year ' + year +
                                '</option>';
                        } else {
                            data.forEach(function(course) {
                                options +=
                                    `<option value="${course.id}">${course.code} - ${course.name}</option>`;
                            });
                        }
                        $courseSelect.html(options).prop('disabled', false);
                    },
                    error: function() {
                        $courseSelect.html('<option value="">Error loading courses</option>');
                    }
                });
            });
        });
    </script>
@endsection
