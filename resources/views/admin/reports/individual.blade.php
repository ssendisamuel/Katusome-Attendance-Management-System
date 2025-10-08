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
        <form class="row g-4" method="GET" action="{{ route('admin.reports.individual') }}">
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
            <p class="mb-0">{{ optional($student->group)->name }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-success-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-user-follow-line icon-22px text-success"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Present Days</p>
              <h4 class="mb-0 text-success">{{ $records->where('status','present')->count() }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-danger-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-user-unfollow-line icon-22px text-danger"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Absences</p>
              <h4 class="mb-0 text-danger">{{ $records->where('status','absent')->count() }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-warning-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-time-line icon-22px text-warning"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Late Arrivals</p>
              <h4 class="mb-0 text-warning">{{ $records->where('status','late')->count() }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="individualTable" class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Course</th>
                <th>Time In</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $row)
              <tr>
                <td>{{ optional($row->marked_at)?->format('Y-m-d') }}</td>
                <td>{{ optional($row->schedule->course)->name }}</td>
                <td>{{ optional($row->marked_at)?->format('H:i') }}</td>
                <td>{{ ucfirst($row->status) }}</td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center">No Data Found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
            <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#individualTable" data-title="Individual Attendance" data-filename="Individual_Attendance_{{ optional($student)->name ? \Illuminate\Support\Str::slug($student->name) : 'Unknown' }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Individual" data-json-url="{{ $student ? route('admin.reports.individual.json',['student_id'=>$student->id]) : '' }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
            <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#individualTable" data-title="Individual Attendance" data-filename="Individual_Attendance_{{ optional($student)->name ? \Illuminate\Support\Str::slug($student->name) : 'Unknown' }}.xlsx" data-json-url="{{ $student ? route('admin.reports.individual.json',['student_id'=>$student->id]) : '' }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
            @if($student)
              <a class="btn btn-outline-success" href="{{ route('admin.reports.individual.csv',['student_id'=>$student->id]) }}"><span class="icon-base ri ri-file-text-line me-1"></span>CSV</a>
            @endif
          </div>
        </div>
        @if(method_exists($records,'links'))
          <div class="card-footer">{{ $records->withQueryString()->links() }}</div>
        @endif
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script>
  (function(){
    const input = document.getElementById('studentSearch');
    const hiddenId = document.getElementById('studentId');
    const list = document.getElementById('studentSuggestions');
    let timer = null;
    let lastQuery = '';

    function clearSuggestions(){
      list.innerHTML = '';
      list.style.display = 'none';
    }

    function renderSuggestions(items){
      list.innerHTML = '';
      items.forEach(item => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = item.label;
        a.addEventListener('click', function(e){
          e.preventDefault();
          input.value = item.label;
          hiddenId.value = item.id;
          clearSuggestions();
        });
        list.appendChild(a);
      });
      list.style.display = items.length ? 'block' : 'none';
    }

    async function fetchSuggestions(q){
      if (!q || q.length < 2) { clearSuggestions(); return; }
      const url = '{{ route('admin.reports.students.search') }}' + '?q=' + encodeURIComponent(q);
      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        if (!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();
        renderSuggestions(data);
      } catch (e) {
        console.error('Student search failed', e);
      }
    }

    input.addEventListener('input', function(){
      const q = input.value.trim();
      hiddenId.value = '';
      if (timer) clearTimeout(timer);
      timer = setTimeout(() => {
        if (q !== lastQuery) {
          lastQuery = q;
          fetchSuggestions(q);
        }
      }, 250);
    });

    document.addEventListener('click', function(e){
      if (!list.contains(e.target) && e.target !== input) {
        clearSuggestions();
      }
    });
  })();
</script>
@endsection