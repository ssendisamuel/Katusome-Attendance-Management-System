<div class="table-responsive">
  <table id="attendancesTableEl" class="table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Course</th>
        <th>Group</th>
        <th>Lecturer</th>
        <th>Status</th>
        <th>Marked At</th>
        <th>Location</th>
        <th>Photo</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($attendances as $attendance)
        <tr>
          <td>{{ optional($attendance->student)->name }}</td>
          <td>{{ optional($attendance->schedule->course)->name }}</td>
          <td>{{ optional($attendance->schedule->group)->name }}</td>
          <td>
            @php($sch = $attendance->schedule)
@php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
            @php($names = ($hasPivot && $sch && $sch->relationLoaded('lecturers') && $sch->lecturers && $sch->lecturers->count()) ? $sch->lecturers->pluck('name')->implode(', ') : optional($sch->lecturer)->name)
            {{ $names ?: '—' }}
          </td>
          <td>{{ ucfirst($attendance->status) }}</td>
          <td>{{ $attendance->marked_at?->format('Y-m-d H:i') }}</td>
          <td>{{ $attendance->lat && $attendance->lng ? $attendance->lat . ', ' . $attendance->lng : '—' }}</td>
          <td>
            @if($attendance->selfie_path)
              @php($exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($attendance->selfie_path))
              @if($exists)
                @php($photoUrl = \Illuminate\Support\Facades\Storage::url($attendance->selfie_path))
                <a href="#" data-photo-url="{{ $photoUrl }}" title="View photo">
                  <img src="{{ $photoUrl }}" alt="Attendance photo" class="img-thumbnail" loading="lazy" style="height: 48px; width: auto;" />
                </a>
              @else
                —
              @endif
            @else
              —
            @endif
          </td>
          <td class="text-end">
            <form action="{{ route('admin.attendance.destroy', $attendance) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attendance?')">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="card-footer">{{ $attendances->appends(request()->query())->links() }}</div>