<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Course</th>
        <th>Group</th>
        <th>Lecturer</th>
        <th>Status</th>
        <th>Marked At</th>
        <th>Location</th>
        <th>Selfie</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($attendances as $attendance)
        <tr>
          <td>{{ optional($attendance->student)->name }}</td>
          <td>{{ optional($attendance->schedule->course)->name }}</td>
          <td>{{ optional($attendance->schedule->group)->name }}</td>
          <td>{{ optional($attendance->schedule->lecturer)->name }}</td>
          <td>{{ ucfirst($attendance->status) }}</td>
          <td>{{ $attendance->marked_at?->format('Y-m-d H:i') }}</td>
          <td>{{ $attendance->lat && $attendance->lng ? $attendance->lat . ', ' . $attendance->lng : '—' }}</td>
          <td>
            @if($attendance->selfie_path)
              <a href="{{ Storage::url($attendance->selfie_path) }}" target="_blank">View</a>
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