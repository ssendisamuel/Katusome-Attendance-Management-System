@extends('layouts/layoutMaster')

@section('title', 'Edit Course Assignment')

@section('content')
    <h4 class="mb-4">Edit Assignment</h4>
    <p class="mb-4">
        <strong>Program:</strong> {{ $program->name }}<br>
        <strong>Course:</strong> {{ $course->code }} - {{ $course->name }}
    </p>

    <div class="card p-4">
        <form method="POST"
            action="{{ route('admin.program-courses.update', ['program' => $program->id, 'course' => $course->id]) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                <select name="year_of_study" class="form-select" required>
                    <option value="">Select Year</option>
                    <option value="1" @selected(old('year_of_study', $course->pivot->year_of_study) == 1)>Year 1</option>
                    <option value="2" @selected(old('year_of_study', $course->pivot->year_of_study) == 2)>Year 2</option>
                    <option value="3" @selected(old('year_of_study', $course->pivot->year_of_study) == 3)>Year 3</option>
                    <option value="4" @selected(old('year_of_study', $course->pivot->year_of_study) == 4)>Year 4</option>
                </select>
                @error('year_of_study')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Semester Offered <span class="text-danger">*</span></label>
                <select name="semester_offered" class="form-select" required>
                    <option value="">Select Semester</option>
                    <option value="Semester 1" @selected(old('semester_offered', $course->pivot->semester_offered) == 'Semester 1')>Semester 1</option>
                    <option value="Semester 2" @selected(old('semester_offered', $course->pivot->semester_offered) == 'Semester 2')>Semester 2</option>
                    <option value="Both" @selected(old('semester_offered', $course->pivot->semester_offered) == 'Both')>Both</option>
                </select>
                @error('semester_offered')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Credit Units <span class="text-danger">*</span></label>
                    <input type="number" name="credit_units" class="form-control"
                        value="{{ old('credit_units', $course->pivot->credit_units ?? 3) }}" min="1" max="20"
                        required>
                    @error('credit_units')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Type <span class="text-danger">*</span></label>
                    <select name="course_type" class="form-select" required>
                        <option value="Core" @selected(old('course_type', $course->pivot->course_type ?? 'Core') == 'Core')>Core</option>
                        <option value="Elective" @selected(old('course_type', $course->pivot->course_type ?? 'Core') == 'Elective')>Elective</option>
                        <option value="Audit" @selected(old('course_type', $course->pivot->course_type ?? 'Core') == 'Audit')>Audit</option>
                    </select>
                    @error('course_type')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary">Update Assignment</button>
            <a href="{{ route('admin.program-courses.index', ['program_id' => $program->id]) }}"
                class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
@endsection
