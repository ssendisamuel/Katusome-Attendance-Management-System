<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Course</th>
        <th>Group</th>
        <th>Lecturer</th>
        <th>Series</th>
        <th>Location</th>
        <th>Start</th>
        <th>End</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($schedules as $schedule)
        <tr>
          <td>{{ optional($schedule->course)->name }}</td>
          <td>{{ optional($schedule->group)->name }}</td>
          <td>{{ optional($schedule->lecturer)->name }}</td>
          <td>{{ optional($schedule->series)->name }}</td>
          <td>{{ $schedule->location }}</td>
          <td>{{ $schedule->start_at?->format('Y-m-d H:i') }}</td>
          <td>{{ $schedule->end_at?->format('Y-m-d H:i') }}</td>
          <td class="text-end">
            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this schedule?')">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="card-footer">{{ $schedules->appends(request()->query())->links() }}</div>