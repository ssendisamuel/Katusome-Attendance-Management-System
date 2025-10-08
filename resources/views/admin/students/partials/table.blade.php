<div class="table-responsive">
  <table class="table" id="studentsTableEl">
    <thead>
      <tr>
        <th>Name</th>
        <th>Program</th>
        <th>Group</th>
        <th>Year</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($students as $student)
        <tr>
          <td>{{ optional($student->user)->name ?? $student->name }}</td>
          <td>{{ optional($student->program)->name }}</td>
          <td>{{ optional($student->group)->name }}</td>
          <td>{{ $student->year_of_study }}</td>
          <td class="text-end">
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger js-delete-student" data-name="{{ $student->name }}">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="card-footer">{{ $students->links() }}</div>