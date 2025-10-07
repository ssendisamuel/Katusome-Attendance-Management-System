@extends('layouts/layoutMaster')

@section('title', 'Schedule Series')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Schedule Series</h4>
  <div>
    <a href="{{ route('admin.series.create') }}" class="btn btn-sm btn-primary px-2">Add Series</a>
    <button type="button" class="btn btn-sm btn-success ms-2 px-2" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">Generate All</button>
  </div>
</div>
@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Course</th>
          <th>Group</th>
          <th>Lecturer</th>
          <th>Dates</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($series as $s)
          <tr>
            <td>{{ $s->name }}</td>
            <td>{{ optional($s->course)->name }}</td>
            <td>{{ optional($s->group)->name }}</td>
            <td>{{ optional($s->lecturer)->name }}</td>
            <td>{{ $s->start_date }} → {{ $s->end_date }}</td>
            <td class="text-end">
              <a href="{{ route('admin.series.edit', $s) }}" class="btn btn-sm btn-outline-primary px-2">Edit</a>
              <button type="button" class="btn btn-sm btn-success ms-1 px-2 py-1" data-bs-toggle="modal" data-bs-target="#generateModal-{{ $s->id }}">Generate</button>
              <form action="{{ route('admin.series.destroy', $s) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger px-2 js-delete-series" data-name="{{ $s->name }}">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $series->links() }}</div>
</div>

@foreach($series as $s)
  <!-- Per-series Generate Modal -->
  <div class="modal fade" id="generateModal-{{ $s->id }}" tabindex="-1" aria-labelledby="generateModalLabel-{{ $s->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="generateModalLabel-{{ $s->id }}">Generate from "{{ $s->name }}"</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ route('admin.series.generate-schedules', $s) }}">
          @csrf
          <div class="modal-body">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="overwrite-{{ $s->id }}" name="overwrite">
              <label class="form-check-label" for="overwrite-{{ $s->id }}">Overwrite existing schedules for this series</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" value="1" id="skipOverlaps-{{ $s->id }}" name="skip_overlaps">
              <label class="form-check-label" for="skipOverlaps-{{ $s->id }}">Skip if overlapping schedule exists for the same group</label>
            </div>
            <div class="alert alert-info mt-3">
              Days: {{ implode(', ', $s->days_of_week ?? []) }} | Time: {{ $s->start_time->format('H:i') }}–{{ $s->end_time->format('H:i') }} | Range: {{ $s->start_date }} → {{ $s->end_date }}
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success px-2">Generate</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<!-- Bulk Generate Modal -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-labelledby="bulkGenerateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkGenerateModalLabel">Generate All Series (Current Term)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('admin.series.generate-all') }}">
        @csrf
        <div class="modal-body">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="bulkOverwrite" name="overwrite">
            <label class="form-check-label" for="bulkOverwrite">Overwrite existing schedules for affected series</label>
          </div>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="bulkSkipOverlaps" name="skip_overlaps">
            <label class="form-check-label" for="bulkSkipOverlaps">Skip if overlapping schedule exists for same group</label>
          </div>
          <div class="alert alert-info mt-3">
            Only series covering today are processed. Uses each series' days/time and date range.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success px-2">Generate All</button>
        </div>
      </form>
    </div>
  </div>
</div>

@isset($audits)
<div class="card mt-4">
  <div class="card-header"><h5 class="mb-0">Generation Audit Log</h5></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>When</th>
            <th>Admin</th>
            <th>Series</th>
            <th>Overwrite</th>
            <th>Skip Overlaps</th>
            <th>Generated Dates</th>
          </tr>
        </thead>
        <tbody>
          @forelse($audits as $audit)
            <tr>
              <td>{{ $audit->created_at }}</td>
              <td>{{ optional($audit->user)->name ?? 'Unknown' }}</td>
              <td>{{ optional($audit->series)->name ?? '#' }}</td>
              <td>{{ $audit->overwrite ? 'Yes' : 'No' }}</td>
              <td>{{ $audit->skip_overlaps ? 'Yes' : 'No' }}</td>
              <td>
                @php $dates = $audit->generated_dates ?? []; @endphp
                {{ is_array($dates) ? implode(', ', $dates) : $dates }}
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center">No generation activity yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $audits->links() }}</div>
</div>
@endisset
@endsection

@section('page-script')
<script>
  (function () {
    document.querySelectorAll('.js-delete-series').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this series';
        if (window.Swal && window.Swal.fire) {
          window.Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
          }).then(function (result) {
            if (result.isConfirmed) form.submit();
          });
        } else {
          if (confirm('Delete ' + name + '?')) form.submit();
        }
      });
    });
  })();
</script>
@endsection