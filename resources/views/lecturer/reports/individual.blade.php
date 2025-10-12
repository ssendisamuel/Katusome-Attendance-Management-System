@extends('layouts/layoutMaster')

@section('title', 'Individual Attendance')

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Individual Attendance</h4>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="card-body">
        <form class="row g-4" method="GET" action="{{ route('lecturer.reports.individual') }}">
          <div class="col-12 col-md-6 position-relative">
            <label class="form-label">Student</label>
            <input type="text" id="studentSearch" class="form-control" placeholder="Search student by name or ID" autocomplete="off" value="{{ optional($student)->name }}">
            <input type="hidden" name="student_id" id="studentId" value="{{ optional($student)->id }}">
            <div id="studentSuggestions" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 240px; overflow:auto; display:none;"></div>
          </div>
          <div class="col-12 col-md-6 d-flex align-items-end justify-content-end">
            <button class="btn btn-primary">Load</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @if($student)
  <div class="col-12">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Student</p>
            <h5 class="mb-0">{{ $student->name }}</h5>
            <p class="mb-0 text-muted">Group: {{ optional($student->group)->name }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Present Days</p>
            <h4 class="mb-0 text-success">{{ $summary['present'] }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Absent Days</p>
            <h4 class="mb-0 text-danger">{{ $summary['absent'] }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Late Days</p>
            <h4 class="mb-0 text-warning">{{ $summary['late'] }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Time</th>
              <th>Course</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($attendances as $row)
              <tr>
                <td>{{ optional($row->marked_at)?->format('Y-m-d') }}</td>
                <td>{{ optional($row->marked_at)?->format('H:i') }}</td>
                <td>{{ optional($row->schedule->course)->name }}</td>
                <td>{{ ucfirst($row->status) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center">No records found for this student.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between">
        <div>{{ $attendances->withQueryString()->links() }}</div>
        <div class="report-actions">
          <a href="{{ route('lecturer.reports.individual.export.csv', request()->query()) }}" class="btn btn-outline-secondary">
            <span class="icon-base ri ri-file-list-2-line me-2"></span> CSV
          </a>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@section('page-style')
<style>
  .report-actions .btn { padding: 0.5rem 1rem; line-height: 1.5; display: inline-flex; align-items: center; }
  .report-actions .btn .icon-base { line-height: 1; }
  #studentSuggestions .list-group-item { cursor: pointer; }
</style>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('studentSearch');
  const hiddenId = document.getElementById('studentId');
  const suggestions = document.getElementById('studentSuggestions');

  let debounceTimer;
  function fetchSuggestions(term) {
    const url = new URL("{{ route('lecturer.reports.students.search') }}", window.location.origin);
    url.searchParams.set('q', term);
    fetch(url)
      .then(r => r.json())
      .then(items => {
        suggestions.innerHTML = '';
        if (!items || !items.length) { suggestions.style.display = 'none'; return; }
        items.forEach(item => {
          const el = document.createElement('a');
          el.className = 'list-group-item list-group-item-action';
          el.textContent = item.label || (item.name + (item.group ? (' ('+item.group+')') : ''));
          el.addEventListener('click', () => {
            searchInput.value = item.name;
            hiddenId.value = item.id;
            suggestions.style.display = 'none';
          });
          suggestions.appendChild(el);
        });
        suggestions.style.display = 'block';
      })
      .catch(() => { suggestions.style.display = 'none'; });
  }

  searchInput.addEventListener('input', function() {
    const term = this.value.trim();
    clearTimeout(debounceTimer);
    if (term.length < 2) { suggestions.style.display = 'none'; hiddenId.value=''; return; }
    debounceTimer = setTimeout(() => fetchSuggestions(term), 300);
  });

  document.addEventListener('click', function(e){
    if (!suggestions.contains(e.target) && e.target !== searchInput) {
      suggestions.style.display = 'none';
    }
  });
});
</script>
@endsection