@extends('layouts/layoutMaster')

@section('title', 'Assign Course to Program')

@section('content')
    <h4 class="mb-4">Assign Course to Program</h4>

    <div class="card p-4">
        <form method="POST" action="{{ route('admin.program-courses.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Program <span class="text-danger">*</span></label>
                <select name="program_id" class="form-select select2" data-placeholder="Select Program" required>
                    <option value="">Select Program</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id', $selectedProgramId) == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
                @error('program_id')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Course <span class="text-danger">*</span></label>
                <select name="course_id" class="form-select select2" data-placeholder="Select Course" required>
                    <option value="">Select Course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} -
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
                @error('course_id')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                <select name="year_of_study" class="form-select" required>
                    <option value="">Select Year</option>
                    <option value="1" @selected(old('year_of_study') == 1)>Year 1</option>
                    <option value="2" @selected(old('year_of_study') == 2)>Year 2</option>
                    <option value="3" @selected(old('year_of_study') == 3)>Year 3</option>
                    <option value="4" @selected(old('year_of_study') == 4)>Year 4</option>
                </select>
                @error('year_of_study')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Semester Offered <span class="text-danger">*</span></label>
                <select name="semester_offered" class="form-select" required>
                    <option value="">Select Semester</option>
                    <option value="Semester 1" @selected(old('semester_offered') == 'Semester 1')>Semester 1</option>
                    <option value="Semester 2" @selected(old('semester_offered') == 'Semester 2')>Semester 2</option>
                    <option value="Both" @selected(old('semester_offered') == 'Both')>Both</option>
                </select>
                @error('semester_offered')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Credit Units <span class="text-danger">*</span></label>
                    <input type="number" name="credit_units" class="form-control" value="{{ old('credit_units', 3) }}"
                        min="1" max="20" required>
                    @error('credit_units')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Type <span class="text-danger">*</span></label>
                    <select name="course_type" class="form-select" required>
                        <option value="Core" @selected(old('course_type') == 'Core')>Core</option>
                        <option value="Elective" @selected(old('course_type') == 'Elective')>Elective</option>
                        <option value="Audit" @selected(old('course_type') == 'Audit')>Audit</option>
                    </select>
                    @error('course_type')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary">Assign</button>
            <a href="{{ route('admin.program-courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js'])
@endsection
