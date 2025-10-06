@extends('layouts/layoutMaster')

@section('title', 'Record Attendance')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Record Attendance — {{ optional($schedule->course)->name }} ({{ optional($schedule->course)->code }})</h4>
  <span class="text-muted">{{ $schedule->start_at->format('H:i') }}–{{ $schedule->end_at->format('H:i') }} @ {{ $schedule->location }}</span>
</div>
@if($errors->any())
  <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card">
  <div class="card-body">
    <div class="row g-4">
      <div class="col-12 col-md-6">
        <ul class="list-unstyled mb-4">
          <li><strong>Student:</strong> {{ $student->name }} ({{ $student->reg_no ?? $student->student_no }})</li>
          <li><strong>Course:</strong> {{ optional($schedule->course)->name }} ({{ optional($schedule->course)->code }})</li>
          <li><strong>Lecturer:</strong> {{ optional($schedule->lecturer)->name ?? '—' }}</li>
          <li><strong>Start Time:</strong> {{ $schedule->start_at->format('H:i') }}</li>
          <li><strong>Current Time:</strong> <span id="currentTime">—</span></li>
        </ul>
        <div class="mb-3">
          <video id="video" autoplay muted playsinline class="w-100 rounded" style="max-height: 280px; background:#000"></video>
        </div>
        <div class="d-flex gap-2">
          <button id="captureBtn" class="btn btn-primary">Capture Attendance</button>
          <button id="retakeBtn" class="btn btn-secondary d-none">Retake Photo</button>
        </div>
        <!-- Fallback inline preview when modal is unavailable -->
        <div id="previewFallback" class="border rounded p-3 d-none mt-3">
          <img id="previewImgFallback" class="img-fluid rounded mb-2" alt="Captured selfie" />
          <div class="small text-muted mb-2">
            <div>Captured at: <span id="capturedAtFallback">—</span></div>
            <div>Captured location: <span id="capturedLocFallback">Detecting…</span></div>
            <div>Distance from MUBS: <span id="capturedDistFallback">—</span></div>
          </div>
          <div class="d-flex gap-2">
            <button id="confirmBtnFallback" class="btn btn-success">Confirm Attendance</button>
            <button id="retakeBtnFallback" class="btn btn-outline-warning">Retake Photo</button>
            <button id="closeFallbackBtn" class="btn btn-outline-secondary">Close</button>
          </div>
        </div>
      </div>
      <!-- Modal for captured preview -->
      <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Confirm Attendance</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <img id="previewImg" class="img-fluid rounded mb-2" alt="Captured selfie" />
              <div class="small text-muted mb-2">
                <div>Captured at: <span id="capturedAt">—</span></div>
                <div>Captured location: <span id="capturedLoc">Detecting…</span></div>
                <div>Distance from MUBS: <span id="capturedDist">—</span></div>
              </div>
            </div>
            <div class="modal-footer">
              <button id="confirmBtn" class="btn btn-success">Confirm Attendance</button>
              <button id="retakeBtnModal" class="btn btn-outline-secondary" data-bs-dismiss="modal">Retake Photo</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form id="submitForm" method="POST" action="{{ route('attendance.checkin.store') }}" enctype="multipart/form-data" class="d-none">
      @csrf
      <input type="hidden" name="schedule_id" value="{{ $schedule->id }}" />
      <input type="hidden" name="lat" id="latInput" />
      <input type="hidden" name="lng" id="lngInput" />
      <input type="file" name="selfie" id="selfieInput" accept="image/*" />
    </form>
  </div>
  <div class="card-footer">
    @if($existing)
      <div class="alert alert-info mb-0">Already marked: {{ ucfirst($existing->status) }} at {{ $existing->marked_at->format('H:i') }}</div>
    @endif
  </div>
</div>

<script>
// Time updater
function updateCurrentTime() {
  const el = document.getElementById('currentTime');
  const now = new Date();
  el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateCurrentTime, 1000); updateCurrentTime();

// Camera
const video = document.getElementById('video');
const captureBtn = document.getElementById('captureBtn');
const retakeBtn = document.getElementById('retakeBtn');
// Modal elements
const previewModalEl = document.getElementById('previewModal');
let previewModal;
if (window.bootstrap && previewModalEl) {
  previewModal = new bootstrap.Modal(previewModalEl);
}
const retakeBtnModal = document.getElementById('retakeBtnModal');
const previewImg = document.getElementById('previewImg');
// Fallback preview elements
const previewFallback = document.getElementById('previewFallback');
const previewImgFallback = document.getElementById('previewImgFallback');
const capturedAtFallback = document.getElementById('capturedAtFallback');
const capturedLocFallback = document.getElementById('capturedLocFallback');
const capturedDistFallback = document.getElementById('capturedDistFallback');
const confirmBtnFallback = document.getElementById('confirmBtnFallback');
const retakeBtnFallback = document.getElementById('retakeBtnFallback');
const closeFallbackBtn = document.getElementById('closeFallbackBtn');
const capturedAt = document.getElementById('capturedAt');
const capturedLoc = document.getElementById('capturedLoc');
const confirmBtn = document.getElementById('confirmBtn');
const capturedDist = document.getElementById('capturedDist');
const backBtn = document.getElementById('backBtn');
const latInput = document.getElementById('latInput');
const lngInput = document.getElementById('lngInput');
const selfieInput = document.getElementById('selfieInput');
const submitForm = document.getElementById('submitForm');

// Toast helper from global (defined in main.js), fallback if missing
const Toast = window.Toast || (window.Swal ? Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true
}) : null);

// Geofence constants (Admin-configured)
const campus = { lat: {{ $setting->latitude }}, lng: {{ $setting->longitude }} };
const radiusMeters = {{ $setting->radius_meters }};
console.log('[Check-In Show] Geofence config', { campus, radiusMeters });
function haversine(lat1, lon1, lat2, lon2){
  const R = 6371000;
  const toRad = x => x * Math.PI / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

async function initCamera() {
  try {
    // Try preferred constraints first
    const constraints = { video: { facingMode: { ideal: 'user' } }, audio: false };
    let stream = await navigator.mediaDevices.getUserMedia(constraints);
    if (!stream || !stream.getVideoTracks().length) throw new Error('No video track');
    video.srcObject = stream;
    await video.play();
  } catch (e) {
    // Fallback for Edge or browsers with limited constraint support
    try {
      const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      video.srcObject = fallbackStream;
      await video.play();
    } catch (err) {
      if (window.Swal) {
        Swal.fire({
          icon: 'error',
          title: 'Camera not available',
          text: 'Please allow camera access or try another device/browser.',
          customClass: { confirmButton: 'btn btn-primary' },
          buttonsStyling: false
        });
      } else {
        alert('Camera permission denied or unavailable.');
      }
    }
  }
}
initCamera();

function dataURLtoFile(dataUrl, filename) {
  const arr = dataUrl.split(','), mime = arr[0].match(/:(.*?);/)[1], bstr = atob(arr[1]);
  let n = bstr.length; const u8arr = new Uint8Array(n);
  while (n--) { u8arr[n] = bstr.charCodeAt(n); }
  return new File([u8arr], filename, { type: mime });
}

function captureImage() {
  // Ensure video has current frame data (fixes black captures on some browsers)
  const ensureReady = () => new Promise(resolve => {
    if (video.readyState >= 2 && video.videoWidth > 0) return resolve();
    video.addEventListener('loadeddata', () => resolve(), { once: true });
  });
  ensureReady().then(() => {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg');
    previewImg.src = dataUrl;
    if (previewImgFallback) previewImgFallback.src = dataUrl;
    const file = dataURLtoFile(dataUrl, 'selfie.jpg');
    const dt = new DataTransfer(); dt.items.add(file); selfieInput.files = dt.files;
  });
}

function getLocation() {
  capturedLoc.textContent = 'Detecting…';
  if (!navigator.geolocation) { capturedLoc.textContent = 'Unavailable'; return; }
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude, longitude } = pos.coords;
    latInput.value = latitude; lngInput.value = longitude;
    capturedLoc.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    if (capturedLocFallback) capturedLocFallback.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    const dist = haversine(campus.lat, campus.lng, latitude, longitude);
    const distRounded = Math.round(dist);
    capturedDist.textContent = `${distRounded}m`;
    if (capturedDistFallback) capturedDistFallback.textContent = `${distRounded}m`;
    // Color distance using theme colors: green within radius, red outside
    capturedDist.classList.remove('text-success', 'text-danger');
    capturedDist.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
    if (capturedDistFallback) {
      capturedDistFallback.classList.remove('text-success', 'text-danger');
      capturedDistFallback.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
    }
    console.log('[Check-In Show] Located', { latitude, longitude, dist });
  }, err => {
    capturedLoc.textContent = 'Unavailable';
    capturedDist.textContent = '—';
    capturedDist.classList.remove('text-success', 'text-danger');
    if (capturedLocFallback) capturedLocFallback.textContent = 'Unavailable';
    if (capturedDistFallback) {
      capturedDistFallback.textContent = '—';
      capturedDistFallback.classList.remove('text-success', 'text-danger');
    }
  }, { enableHighAccuracy: true, timeout: 10000 });
}

captureBtn.addEventListener('click', (e) => {
  e.preventDefault();
  captureImage();
  const ts = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  capturedAt.textContent = ts;
  if (capturedAtFallback) capturedAtFallback.textContent = ts;
  getLocation();
  // Show modal with preview
  if (previewModal) { previewModal.show(); }
  else if (previewFallback) { previewFallback.classList.remove('d-none'); }
  retakeBtn.classList.remove('d-none');
  // Hide capture button after first capture to avoid duplicate actions
  captureBtn.classList.add('d-none');
  // Hide webcam while previewing the captured photo
  video.classList.add('d-none');
});

retakeBtn.addEventListener('click', (e) => {
  e.preventDefault();
  // Switch back to webcam view and allow a new capture
  if (previewModal) { previewModal.hide(); }
  if (previewFallback) { previewFallback.classList.add('d-none'); }
  video.classList.remove('d-none');
  captureBtn.classList.remove('d-none');
  retakeBtn.classList.add('d-none');
});

// Retake from modal closes modal and refreshes capture
if (retakeBtnModal) {
  retakeBtnModal.addEventListener('click', (e) => {
    e.preventDefault();
    // Close modal and switch to webcam for a fresh capture
    if (previewModal) { previewModal.hide(); }
    if (previewFallback) { previewFallback.classList.add('d-none'); }
    video.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
  });
}

// Close fallback preview container
if (closeFallbackBtn && previewFallback) {
  closeFallbackBtn.addEventListener('click', (e) => {
    e.preventDefault();
    previewFallback.classList.add('d-none');
    // Return to webcam so the student can try again or cancel
    video.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
  });
}

// Retake from fallback preview: return to webcam for a fresh capture
if (retakeBtnFallback) {
  retakeBtnFallback.addEventListener('click', (e) => {
    e.preventDefault();
    if (previewModal) { previewModal.hide(); }
    if (previewFallback) { previewFallback.classList.add('d-none'); }
    video.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
  });
}

confirmBtn.addEventListener('click', (e) => {
  e.preventDefault();
  const lt = parseFloat(latInput.value), ln = parseFloat(lngInput.value);
  if(isFinite(lt) && isFinite(ln)){
    const dist = haversine(campus.lat, campus.lng, lt, ln);
    const distRounded = Math.round(dist);
    console.log('[Check-In Show] Confirm distance check', { lt, ln, dist, radiusMeters, campus });
    if(dist > radiusMeters){
      if (window.Swal) {
        Swal.fire({
          icon: 'warning',
          title: 'Outside allowed area',
          text: `Attendance can only be recorded from within MUBS premises.\nOutside MUBS premises (${distRounded}m).`,
          customClass: { confirmButton: 'btn btn-primary' },
          buttonsStyling: false
        });
      } else {
        alert(`Attendance can only be recorded from within MUBS premises.\nOutside MUBS premises (${distRounded}m).`);
      }
      return;
    }
  }
  if (window.Swal) {
    Swal.fire({
      icon: 'question',
      title: 'Confirm attendance?',
      text: 'Make sure the photo and location are correct.',
      showCancelButton: true,
      confirmButtonText: 'Yes, confirm',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-success me-2',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        // Disable button and show inline spinner while posting
        const originalHtml = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.classList.add('disabled');
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';

        const originalAction = submitForm.action;
        const formData = new FormData(submitForm);

        fetch(originalAction, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          if (response.redirected) {
            // Success - show toast and redirect
            if (Toast) {
              Toast.fire({ icon: 'success', title: 'Attendance recorded!' }).then(() => {
                if (previewModal) previewModal.hide();
                if (previewFallback) previewFallback.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                retakeBtn.classList.add('d-none');
                window.location.href = response.url;
              });
            } else {
              if (previewModal) previewModal.hide();
              if (previewFallback) previewFallback.classList.add('d-none');
              captureBtn.classList.remove('d-none');
              retakeBtn.classList.add('d-none');
              window.location.href = response.url;
            }
          } else if (response.ok) {
            // Non-redirect success; attempt to proceed
            if (Toast) Toast.fire({ icon: 'success', title: 'Attendance recorded!' });
            if (previewModal) previewModal.hide();
            if (previewFallback) previewFallback.classList.add('d-none');
            captureBtn.classList.remove('d-none');
            retakeBtn.classList.add('d-none');
          } else {
            return response.text().then(text => { throw new Error(text); });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Submission failed',
            text: 'There was an error submitting your attendance. Please try again.',
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false
          });
        })
        .finally(() => {
          // Restore button state unless redirected (page unload will cancel this anyway)
          confirmBtn.disabled = false;
          confirmBtn.classList.remove('disabled');
          confirmBtn.innerHTML = originalHtml;
        });
      }
    });
  } else {
    if (confirm('Confirm attendance?')) {
      // Fallback for browsers without SweetAlert - just submit normally
      submitForm.submit();
    }
  }
});

// Bind fallback confirm button to reuse the same handler
if (confirmBtnFallback) {
  confirmBtnFallback.addEventListener('click', (e) => {
    e.preventDefault();
    confirmBtn.click();
  });
}
</script>
@endsection