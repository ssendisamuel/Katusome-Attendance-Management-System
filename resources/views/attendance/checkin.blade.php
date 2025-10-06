@extends('layouts/layoutMaster')

@section('title', 'Student Check-In')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Check-In</h4>
  <span class="text-muted">Welcome, {{ $student->name }}</span>
  </div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@error('schedule_id')
  <div class="alert alert-danger">{{ $message }}</div>
@enderror
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('attendance.checkin.store') }}" enctype="multipart/form-data" id="checkinForm" class="row g-4">
      @csrf
      <div class="col-12 col-md-6">
        <label class="form-label">Today’s Class</label>
        <select name="schedule_id" class="form-select" required>
          <option value="">Select schedule</option>
          @forelse($schedules as $schedule)
            <option value="{{ $schedule->id }}">
              {{ optional($schedule->course)->name }} — {{ $schedule->start_at->format('H:i') }} at {{ $schedule->location }} ({{ optional($schedule->lecturer)->name }})
            </option>
          @empty
            <option value="">No classes today</option>
          @endforelse
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Selfie (optional)</label>
        <input type="file" name="selfie" accept="image/*" capture="environment" class="form-control" />
      </div>
      <div class="col-12">
        <label class="form-label">Your Location</label>
        <div class="d-flex gap-3">
          <input type="text" name="lat" id="lat" class="form-control" placeholder="Latitude" readonly />
          <input type="text" name="lng" id="lng" class="form-control" placeholder="Longitude" readonly />
          <button type="button" id="btnLocate" class="btn btn-outline-secondary">Use current location</button>
        </div>
        <small id="locStatus" class="text-muted d-block mt-2">Location not set</small>
        <small class="text-muted d-block">Distance from MUBS: <span id="locDist">—</span></small>
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Mark Attendance</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    // Geofence constants (Admin-configured)
    const campus = { lat: {{ $setting->latitude }}, lng: {{ $setting->longitude }} };
    const radiusMeters = {{ $setting->radius_meters }};
    console.log('[Check-In] Geofence config', { campus, radiusMeters });

    function haversine(lat1, lon1, lat2, lon2){
      const R = 6371000;
      const toRad = x => x * Math.PI / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
      return R * c;
    }

    const btn = document.getElementById('btnLocate');
    const lat = document.getElementById('lat');
    const lng = document.getElementById('lng');
    const status = document.getElementById('locStatus');
    const distEl = document.getElementById('locDist');
    const form = document.getElementById('checkinForm');
    if(btn){
      btn.addEventListener('click', function(){
        if(!navigator.geolocation){
          status.textContent = 'Geolocation is not supported by your browser.';
          status.classList.remove('text-muted');
          status.classList.add('text-danger');
          return;
        }
        status.textContent = 'Locating…';
        navigator.geolocation.getCurrentPosition(function(pos){
          lat.value = pos.coords.latitude.toFixed(6);
          lng.value = pos.coords.longitude.toFixed(6);
          const dist = haversine(campus.lat, campus.lng, pos.coords.latitude, pos.coords.longitude);
          distEl.textContent = Math.round(dist) + 'm';
          // Color distance using theme colors
          distEl.classList.remove('text-success', 'text-danger');
          distEl.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
          status.textContent = 'Location acquired.';
          status.classList.remove('text-danger');
          status.classList.add('text-success');
          console.log('[Check-In] Current position', { lat: pos.coords.latitude, lng: pos.coords.longitude, dist });
          if(dist > radiusMeters){
            status.textContent = 'Outside MUBS premises (' + Math.round(dist) + 'm).';
            status.classList.remove('text-success');
            status.classList.add('text-danger');
          }
          // Show quick hint of allowed center and radius
          const hint = `Allowed center: ${campus.lat.toFixed(6)}, ${campus.lng.toFixed(6)} | Radius: ${radiusMeters}m`;
          status.title = hint;
        }, function(err){
          status.textContent = 'Unable to retrieve location: ' + err.message;
          status.classList.remove('text-muted');
          status.classList.add('text-danger');
          distEl.textContent = '—';
          distEl.classList.remove('text-success', 'text-danger');
        }, { enableHighAccuracy: true, timeout: 8000 });
      });
    }

    if(form){
      form.addEventListener('submit', function(e){
        const lt = parseFloat(lat.value), ln = parseFloat(lng.value);
        if(!isFinite(lt) || !isFinite(ln)) return; // let backend validate
        const dist = haversine(campus.lat, campus.lng, lt, ln);
        distEl.textContent = Math.round(dist) + 'm';
        distEl.classList.remove('text-success', 'text-danger');
        distEl.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
        console.log('[Check-In] Submit distance check', { lt, ln, dist, radiusMeters, campus });
        if(dist > radiusMeters){
          e.preventDefault();
          status.textContent = 'Attendance can only be recorded from within MUBS premises. Outside MUBS premises (' + Math.round(dist) + 'm).';
          status.classList.remove('text-success');
          status.classList.add('text-danger');
        }
      });
    }
  })();
</script>
@endsection