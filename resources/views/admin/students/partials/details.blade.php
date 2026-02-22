<div class="text-center mb-4">
  <div class="avatar avatar-xl mx-auto mb-2" style="width: 100px; height: 100px;">
    @if ($student->selfie_path)
      <img src="{{ \Illuminate\Support\Facades\Storage::url($student->selfie_path) }}" alt="{{ $student->name }}"
        class="rounded-circle w-100 h-100 object-fit-cover">
    @else
      <span
        class="avatar-initial rounded-circle bg-label-primary display-4">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
    @endif
  </div>
  <h4>{{ $student->name }}</h4>
  <p class="text-muted mb-0">{{ $student->email }}</p>
</div>

<div class="row g-3">
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Student No</small>
      <span class="fw-bold">{{ $student->student_no }}</span>
    </div>
  </div>
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Registration No</small>
      <span class="fw-bold">{{ $student->reg_no }}</span>
    </div>
  </div>
  <div class="col-12">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Program</small>
      <span class="fw-bold">{{ optional($student->program)->name ?? 'N/A' }}</span>
    </div>
  </div>
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Group</small>
      <span class="fw-bold">{{ optional($student->group)->name ?? 'N/A' }}</span>
    </div>
  </div>
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Year of Study</small>
      <span class="fw-bold">Year {{ $student->year_of_study }}</span>
    </div>
  </div>
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Phone</small>
      <span class="fw-bold">{{ $student->phone ?? '—' }}</span>
    </div>
  </div>
  <div class="col-6">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Gender</small>
      <span class="fw-bold">{{ ucfirst($student->gender) ?? '—' }}</span>
    </div>
  </div>
</div>
<div class="mt-4 text-center">
  <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary" target="_blank">Edit Student</a>
</div>
