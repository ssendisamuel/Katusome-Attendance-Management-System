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
@php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
          <li><strong>Lecturer:</strong> {{ ($hasPivot && $schedule->relationLoaded('lecturers') && $schedule->lecturers && $schedule->lecturers->count()) ? $schedule->lecturers->pluck('name')->implode(', ') : (optional($schedule->lecturer)->name ?? '—') }}</li>
          <li><strong>Start Time:</strong> {{ $schedule->start_at->format('H:i') }}</li>
          <li><strong>Current Time:</strong> <span id="currentTime">—</span></li>
        </ul>
        <div class="mb-3">
          <video id="video" autoplay muted playsinline class="w-100 rounded" style="max-height: 280px; background:#000"></video>
        </div>
        <div class="d-flex gap-2">
          <button id="captureBtn" class="btn btn-primary">Capture Attendance</button>
        </div>
        <!-- Fallback inline preview when modal is unavailable -->
        <div id="previewFallback" class="border rounded p-3 d-none mt-3">
          <img id="previewImgFallback" class="img-fluid rounded mb-2" alt="Captured selfie" />
          <div class="small text-muted mb-2">
            <div>Captured at: <span id="capturedAtFallback">—</span></div>
            <div>Captured location: <span id="capturedLocFallback">Detecting…</span></div>
            <div>Distance from MUBS: <span id="capturedDistFallback">—</span></div>
            <div>Accuracy: <span id="capturedAccFallback">—</span></div>
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
                <div>Accuracy: <span id="capturedAcc">—</span></div>
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
      <input type="hidden" name="accuracy" id="accuracyInput" />
      <input type="hidden" name="distance_meters" id="distanceInput" />
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
const capturedAccFallback = document.getElementById('capturedAccFallback');
const confirmBtnFallback = document.getElementById('confirmBtnFallback');
const retakeBtnFallback = document.getElementById('retakeBtnFallback');
const closeFallbackBtn = document.getElementById('closeFallbackBtn');
const capturedAt = document.getElementById('capturedAt');
const capturedLoc = document.getElementById('capturedLoc');
const confirmBtn = document.getElementById('confirmBtn');
const capturedDist = document.getElementById('capturedDist');
const capturedAcc = document.getElementById('capturedAcc');
const backBtn = document.getElementById('backBtn');
const latInput = document.getElementById('latInput');
const lngInput = document.getElementById('lngInput');
const accuracyInput = document.getElementById('accuracyInput');
const distanceInput = document.getElementById('distanceInput');
const selfieInput = document.getElementById('selfieInput');
const submitForm = document.getElementById('submitForm');
const pageDist = document.getElementById('pageDist');
const pageLocStatus = document.getElementById('pageLocStatus');
const pageAcc = document.getElementById('pageAcc');

// Minimal inline modal fallback when SweetAlert is unavailable
function showInlineModal(title, text) {
  const wrap = document.createElement('div');
  wrap.innerHTML = `
    <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:9999;">
      <div style="background:#fff;padding:16px 20px;border-radius:8px;max-width:420px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <h5 style="margin:0 0 8px;font-weight:600;">${title}</h5>
        <p style="margin:0 0 12px;">${text}</p>
        <button id="inlineModalOk" class="btn btn-primary">OK</button>
      </div>
    </div>`;
  document.body.appendChild(wrap);
  const ok = document.getElementById('inlineModalOk');
  ok?.addEventListener('click', () => wrap.remove(), { once: true });
}

// Gating for confirm action based on location
let locationReady = false;
let withinRadius = false;
let outsideNoticeShown = false;
let outsideReminderTimer = null;
const OUTSIDE_REMINDER_MS = 15000; // gentle reminder every 15s

function startOutsideReminder(){
  if (outsideReminderTimer) return;
  outsideReminderTimer = setInterval(() => {
    const disabled = !locationReady || !withinRadius;
    // Stop reminding once user is within bounds or location is missing
    if (!disabled) { stopOutsideReminder(); return; }
    const baseMsg = 'Move within MUBS premises to record attendance.';
    const distText = (pageDist && pageDist.textContent && pageDist.textContent !== '—')
      ? pageDist.textContent
      : (capturedDist && capturedDist.textContent && capturedDist.textContent !== '—')
        ? capturedDist.textContent
        : '';
    const text = distText ? `Outside MUBS premises (${distText}). ${baseMsg}` : baseMsg;
    if (Toast) {
      Toast.fire({ icon: 'info', title: text });
    } else if (window.Swal && !Swal.isVisible()) {
      Swal.fire({ icon: 'info', title: 'Outside allowed area', text, customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
    }
  }, OUTSIDE_REMINDER_MS);
}

function stopOutsideReminder(){
  if (outsideReminderTimer) {
    clearInterval(outsideReminderTimer);
    outsideReminderTimer = null;
  }
}
function updateConfirmState(){
  const disabled = !locationReady || !withinRadius;
  if (confirmBtn) {
    confirmBtn.disabled = disabled;
    confirmBtn.classList.toggle('disabled', disabled);
  }
  if (confirmBtnFallback) {
    confirmBtnFallback.disabled = disabled;
    confirmBtnFallback.classList.toggle('disabled', disabled);
  }
  // Auto-notify when outside the allowed radius even if button is disabled
  if (locationReady && !withinRadius && !outsideNoticeShown) {
    const baseMsg = 'Attendance can only be recorded from within MUBS premises.';
    // Try to include current distance in the message if available
    const distText = (pageDist && pageDist.textContent && pageDist.textContent !== '—')
      ? pageDist.textContent
      : (capturedDist && capturedDist.textContent && capturedDist.textContent !== '—')
        ? capturedDist.textContent
        : '';
    const msg = distText ? `${baseMsg} Outside MUBS premises (${distText}).` : baseMsg;
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Outside allowed area',
        text: msg,
        customClass: { confirmButton: 'btn btn-primary' },
        buttonsStyling: false
      });
    } else {
      showInlineModal('Outside allowed area', msg);
    }
    outsideNoticeShown = true;
  }
  // Start/stop gentle periodic reminders while outside bounds
  if (locationReady && !withinRadius) startOutsideReminder(); else stopOutsideReminder();
}

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

// Acquire a precise location fix by sampling positions until desired accuracy or timeout
function getAccuratePosition(desiredAccuracy = 100, maxWaitMs = 15000) {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation unsupported'));
      return;
    }
    let bestPos = null;
    let settled = false;
    const opts = { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 };
    const watchId = navigator.geolocation.watchPosition(
      (pos) => {
        bestPos = pos;
        const acc = pos?.coords?.accuracy ?? Infinity;
        if (typeof acc === 'number' && isFinite(acc)) {
          // Update inline status while sampling
          if (pageAcc) pageAcc.textContent = `${Math.round(acc)}m`;
          if (pageLocStatus) pageLocStatus.textContent = acc <= desiredAccuracy
            ? 'Location acquired.'
            : `Getting a precise location… (${Math.round(acc)}m)`;
        }
        if (!settled && acc <= desiredAccuracy) {
          settled = true;
          navigator.geolocation.clearWatch(watchId);
          resolve({ pos, timedOut: false });
        }
      },
      (err) => {
        if (!settled) {
          settled = true;
          navigator.geolocation.clearWatch(watchId);
          reject(err);
        }
      },
      opts
    );
    const timeoutId = setTimeout(() => {
      if (!settled) {
        settled = true;
        navigator.geolocation.clearWatch(watchId);
        if (bestPos) {
          resolve({ pos: bestPos, timedOut: true });
        } else {
          reject(new Error('Timed out'));
        }
      }
    }, maxWaitMs);
  });
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
        showInlineModal('Camera not available', 'Please allow camera access or try another device/browser.');
      }
    }
  }
}
// Prompt for permissions on load, then initialize camera and location.
function promptPermissionsAndInit(){
  if (window.Swal) {
    Swal.fire({
      icon: 'info',
      title: 'Permissions Required',
      html: 'Please allow <strong>camera</strong> and <strong>location</strong> access to record attendance.<br/>On iOS Safari, ensure the site has camera access in Settings.',
      confirmButtonText: 'OK, proceed',
      customClass: { confirmButton: 'btn btn-primary' },
      buttonsStyling: false
    }).then(() => {
      // Initialize camera using this user gesture to satisfy Safari
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        initCamera();
      } else {
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: 'Camera unsupported', text: 'Your browser does not support camera access.', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
        }
      }
      // Also request location immediately
      getLocation();
    });
  } else {
    // If SweetAlert isn't available yet, wait briefly then try again
    setTimeout(() => {
      if (window.Swal) {
        promptPermissionsAndInit();
        return;
      }
      // As an ultimate fallback, use a minimal inline modal UI instead of native alert
      const fallback = document.createElement('div');
      fallback.innerHTML = `
        <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:9999;">
          <div style="background:#fff;padding:16px 20px;border-radius:8px;max-width:360px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h5 style="margin:0 0 8px;font-weight:600;">Permissions Required</h5>
            <p style="margin:0 0 12px;">Please allow <strong>camera</strong> and <strong>location</strong> access to record attendance.</p>
            <button id="permFallbackOk" class="btn btn-primary">OK, proceed</button>
          </div>
        </div>`;
      document.body.appendChild(fallback);
      const okBtn = document.getElementById('permFallbackOk');
      okBtn?.addEventListener('click', () => {
        fallback.remove();
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) initCamera();
        getLocation();
      });
    }, 150);
  }
}
// Ensure DOM ready and SweetAlert loaded before prompting
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    // Defer slightly to allow Vite scripts to attach
    setTimeout(() => promptPermissionsAndInit(), 100);
  });
} else {
  setTimeout(() => promptPermissionsAndInit(), 100);
}

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

async function getLocation() {
  capturedLoc.textContent = 'Detecting…';
  if (capturedAcc) capturedAcc.textContent = '—';
  if (capturedAccFallback) capturedAccFallback.textContent = '—';
  if (pageAcc) pageAcc.textContent = '—';
  if (pageLocStatus) { pageLocStatus.textContent = 'Getting a precise location…'; pageLocStatus.classList.remove('text-danger'); }
  // Reset outside notice so we can notify again for a new fix
  outsideNoticeShown = false;
  stopOutsideReminder();
  if (!navigator.geolocation) {
    capturedLoc.textContent = 'Unavailable';
    if (pageLocStatus) { pageLocStatus.textContent = 'Geolocation unsupported on this device.'; pageLocStatus.classList.add('text-danger'); }
    locationReady = false; withinRadius = false; updateConfirmState();
    return;
  }
  try {
    const { pos, timedOut } = await getAccuratePosition(100, 15000);
    const { latitude, longitude, accuracy } = pos.coords;
    latInput.value = latitude; lngInput.value = longitude;
    if (accuracyInput) accuracyInput.value = typeof accuracy === 'number' ? Math.round(accuracy) : '';
    capturedLoc.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    if (capturedLocFallback) capturedLocFallback.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    const dist = haversine(campus.lat, campus.lng, latitude, longitude);
    const distRounded = Math.round(dist);
    if (distanceInput) distanceInput.value = distRounded;
    capturedDist.textContent = `${distRounded}m`;
    if (capturedDistFallback) capturedDistFallback.textContent = `${distRounded}m`;
    // Color distance using theme colors: green within radius, red outside
    capturedDist.classList.remove('text-success', 'text-danger');
    capturedDist.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
    if (capturedDistFallback) {
      capturedDistFallback.classList.remove('text-success', 'text-danger');
      capturedDistFallback.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
    }
    if (pageDist) {
      pageDist.textContent = `${distRounded}m`;
      pageDist.classList.remove('text-success', 'text-danger');
      pageDist.classList.add(dist > radiusMeters ? 'text-danger' : 'text-success');
    }
    if (capturedAcc) capturedAcc.textContent = (typeof accuracy === 'number') ? `${Math.round(accuracy)}m` : '—';
    if (capturedAccFallback) capturedAccFallback.textContent = (typeof accuracy === 'number') ? `${Math.round(accuracy)}m` : '—';
    if (pageAcc) pageAcc.textContent = (typeof accuracy === 'number') ? `${Math.round(accuracy)}m` : '—';
    if (pageLocStatus) {
      const usingCoarse = timedOut && typeof accuracy === 'number' && accuracy > 100;
      pageLocStatus.textContent = usingCoarse ? `Using less precise location (~${Math.round(accuracy)}m)` : 'Location acquired.';
      // If outside radius, reflect that immediately in status for clarity
      if (dist > radiusMeters) {
        pageLocStatus.textContent = `Outside MUBS premises (${distRounded}m).`;
        pageLocStatus.classList.add('text-danger');
      } else {
        pageLocStatus.classList.remove('text-danger');
      }
    }
    // Gating: wait for good accuracy unless we timed out
    const goodAccuracy = typeof accuracy === 'number' && accuracy <= 100;
    locationReady = goodAccuracy || timedOut;
    withinRadius = dist <= radiusMeters;
    updateConfirmState();
    console.log('[Check-In Show] Located', { latitude, longitude, dist, accuracy, timedOut });
  } catch (err) {
    capturedLoc.textContent = 'Unavailable';
    capturedDist.textContent = '—';
    capturedDist.classList.remove('text-success', 'text-danger');
    if (capturedLocFallback) capturedLocFallback.textContent = 'Unavailable';
    if (capturedDistFallback) {
      capturedDistFallback.textContent = '—';
      capturedDistFallback.classList.remove('text-success', 'text-danger');
    }
    if (pageAcc) pageAcc.textContent = '—';
    if (pageDist) { pageDist.textContent = '—'; pageDist.classList.remove('text-success', 'text-danger'); }
    if (pageLocStatus) { pageLocStatus.textContent = 'Location permission denied or unavailable.'; pageLocStatus.classList.add('text-danger'); }
    locationReady = false; withinRadius = false; updateConfirmState();
  }
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
  if (retakeBtn) retakeBtn.classList.remove('d-none');
  // Hide capture button after first capture to avoid duplicate actions
  captureBtn.classList.add('d-none');
  // Hide webcam while previewing the captured photo
  video.classList.add('d-none');
});

if (retakeBtn) {
  retakeBtn.addEventListener('click', (e) => {
    e.preventDefault();
    // Switch back to webcam view and allow a new capture
    if (previewModal) { previewModal.hide(); }
    if (previewFallback) { previewFallback.classList.add('d-none'); }
    video.classList.remove('d-none');
    captureBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
  });
}

// Retake from modal closes modal and refreshes capture
if (retakeBtnModal) {
  retakeBtnModal.addEventListener('click', (e) => {
    e.preventDefault();
    // Close modal and switch to webcam for a fresh capture
  if (previewModal) { previewModal.hide(); }
  if (previewFallback) { previewFallback.classList.add('d-none'); }
  video.classList.remove('d-none');
  captureBtn.classList.remove('d-none');
  if (retakeBtn) retakeBtn.classList.add('d-none');
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
    if (retakeBtn) retakeBtn.classList.add('d-none');
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
    if (retakeBtn) retakeBtn.classList.add('d-none');
  });
}

confirmBtn.addEventListener('click', (e) => {
  e.preventDefault();
  // Guard: require location and within radius
  if (confirmBtn.disabled) {
    const msg = 'Please enable location and ensure you are within MUBS radius to confirm attendance.';
    if (window.Swal) {
      Swal.fire({ icon: 'info', title: 'Enable location', text: msg, customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
    } else { showInlineModal('Enable location', msg); }
    return;
  }
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
        showInlineModal('Outside allowed area', `Attendance can only be recorded from within MUBS premises. Outside MUBS premises (${distRounded}m).`);
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
    }).then(async result => {
      if (result.isConfirmed) {
        // Disable button and show inline spinner while posting
        const originalHtml = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.classList.add('disabled');
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Confirming attendance…';

        const originalAction = submitForm.action;
        const formData = new FormData(submitForm);

        fetch(originalAction, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(async response => {
          // Try to parse JSON if available
          let data = null;
          const contentType = response.headers.get('content-type') || '';
          if (contentType.includes('application/json')) {
            data = await response.json().catch(() => null);
          }

          if (response.redirected) {
            const title = (data && data.message) ? data.message : 'Attendance recorded!';
            if (Toast) { await Toast.fire({ icon: 'success', title }); }
            else if (window.Swal) { await Swal.fire({ icon: 'success', title, customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }); }
            if (previewModal) previewModal.hide();
            if (previewFallback) previewFallback.classList.add('d-none');
            if (captureBtn) captureBtn.classList.remove('d-none');
            if (typeof retakeBtn !== 'undefined' && retakeBtn) retakeBtn.classList.add('d-none');
            window.location.href = response.url;
          } else if (response.ok) {
            const redirectUrl = (data && data.redirect) ? data.redirect : (data && data.url) ? data.url : null;
            const title = (data && data.message) ? data.message : 'Attendance recorded!';
            if (Toast) { await Toast.fire({ icon: 'success', title }); }
            else if (window.Swal) { await Swal.fire({ icon: 'success', title, customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }); }
            if (previewModal) previewModal.hide();
            if (previewFallback) previewFallback.classList.add('d-none');
            if (captureBtn) captureBtn.classList.remove('d-none');
            if (typeof retakeBtn !== 'undefined' && retakeBtn) retakeBtn.classList.add('d-none');
            if (redirectUrl) {
              window.location.href = redirectUrl;
            }
          } else {
            const text = data ? JSON.stringify(data) : await response.text();
            throw new Error(text || 'Submission failed');
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