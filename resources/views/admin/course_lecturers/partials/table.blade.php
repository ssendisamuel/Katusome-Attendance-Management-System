<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Code</th>
        <th>Course</th>
        <th>Program</th>
        <th>Lecturers</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($courses as $course)
      <tr>
        <td>{{ $course->code }}</td>
        <td>{{ $course->name }}</td>
        <td>{{ optional($course->program)->name }}</td>
        <td>
          @php($names = $course->lecturers->map(function($l){ return optional($l->user)->name ?? 'Unknown'; })->implode(', '))
          {{ $names ?: '—' }}
        </td>
        <td class="text-end">
          <a href="{{ route('admin.course-lecturers.edit', $course) }}" class="btn btn-sm btn-primary">Assign</a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="5" class="text-center">No courses found</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>
@if(method_exists($courses, 'links'))
  <div class="mt-3">{{ $courses->links() }}</div>
@endif