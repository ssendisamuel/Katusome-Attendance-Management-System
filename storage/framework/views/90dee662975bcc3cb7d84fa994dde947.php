<?php $__env->startSection('title', 'Record Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Record Attendance — <?php echo e(optional($schedule->course)->name); ?> (<?php echo e(optional($schedule->course)->code); ?>)</h4>
  <span class="text-muted"><?php echo e($schedule->start_at->format('H:i')); ?>–<?php echo e($schedule->end_at->format('H:i')); ?> @ <?php echo e($schedule->location); ?></span>
</div>
<?php if($errors->any()): ?>
  <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <div class="row g-4">
      <div class="col-12 col-md-6">
        <ul class="list-unstyled mb-4">
          <li><strong>Student:</strong> <?php echo e($student->name); ?> (<?php echo e($student->reg_no ?? $student->student_no); ?>)</li>
          <li><strong>Course:</strong> <?php echo e(optional($schedule->course)->name); ?> (<?php echo e(optional($schedule->course)->code); ?>)</li>
          <li><strong>Lecturer:</strong> <?php echo e(optional($schedule->lecturer)->name ?? '—'); ?></li>
          <li><strong>Start Time:</strong> <?php echo e($schedule->start_at->format('H:i')); ?></li>
          <li><strong>Current Time:</strong> <span id="currentTime">—</span></li>
        </ul>
        <div class="mb-3">
          <video id="video" autoplay playsinline class="w-100 rounded" style="max-height: 280px; background:#000"></video>
        </div>
        <div class="d-flex gap-2">
          <button id="captureBtn" class="btn btn-primary">Capture Attendance</button>
          <button id="retakeBtn" class="btn btn-secondary d-none">Retake Photo</button>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div id="preview" class="border rounded p-3 d-none">
          <img id="previewImg" class="img-fluid rounded mb-2" alt="Captured selfie" />
          <div class="small text-muted mb-2">
            <div>Captured at: <span id="capturedAt">—</span></div>
            <div>Captured location: <span id="capturedLoc">Detecting…</span></div>
          </div>
          <div class="d-flex gap-2">
            <button id="confirmBtn" class="btn btn-success">Confirm Attendance</button>
            <button id="backBtn" class="btn btn-outline-secondary">Back</button>
          </div>
        </div>
      </div>
    </div>
    <form id="submitForm" method="POST" action="<?php echo e(route('attendance.checkin.store')); ?>" enctype="multipart/form-data" class="d-none">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="schedule_id" value="<?php echo e($schedule->id); ?>" />
      <input type="hidden" name="lat" id="latInput" />
      <input type="hidden" name="lng" id="lngInput" />
      <input type="file" name="selfie" id="selfieInput" accept="image/*" />
    </form>
  </div>
  <div class="card-footer">
    <?php if($existing): ?>
      <div class="alert alert-info mb-0">Already marked: <?php echo e(ucfirst($existing->status)); ?> at <?php echo e($existing->marked_at->format('H:i')); ?></div>
    <?php endif; ?>
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
const preview = document.getElementById('preview');
const previewImg = document.getElementById('previewImg');
const capturedAt = document.getElementById('capturedAt');
const capturedLoc = document.getElementById('capturedLoc');
const confirmBtn = document.getElementById('confirmBtn');
const backBtn = document.getElementById('backBtn');
const latInput = document.getElementById('latInput');
const lngInput = document.getElementById('lngInput');
const selfieInput = document.getElementById('selfieInput');
const submitForm = document.getElementById('submitForm');

// Geofence constants (MUBS ADB Building)
const campus = { lat: 0.332931, lng: 32.621927 };
const radiusMeters = 150;
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
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    video.srcObject = stream;
  } catch (e) {
    alert('Camera permission denied or unavailable.');
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
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0);
  const dataUrl = canvas.toDataURL('image/jpeg');
  previewImg.src = dataUrl;
  const file = dataURLtoFile(dataUrl, 'selfie.jpg');
  const dt = new DataTransfer(); dt.items.add(file); selfieInput.files = dt.files;
}

function getLocation() {
  capturedLoc.textContent = 'Detecting…';
  if (!navigator.geolocation) { capturedLoc.textContent = 'Unavailable'; return; }
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude, longitude } = pos.coords;
    latInput.value = latitude; lngInput.value = longitude;
    capturedLoc.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
  }, err => {
    capturedLoc.textContent = 'Unavailable';
  }, { enableHighAccuracy: true, timeout: 10000 });
}

captureBtn.addEventListener('click', (e) => {
  e.preventDefault();
  captureImage();
  capturedAt.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  getLocation();
  preview.classList.remove('d-none');
  retakeBtn.classList.remove('d-none');
});

retakeBtn.addEventListener('click', (e) => {
  e.preventDefault();
  captureImage();
  capturedAt.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  getLocation();
});

backBtn.addEventListener('click', (e) => {
  e.preventDefault();
  preview.classList.add('d-none');
});

confirmBtn.addEventListener('click', (e) => {
  e.preventDefault();
  const lt = parseFloat(latInput.value), ln = parseFloat(lngInput.value);
  if(isFinite(lt) && isFinite(ln)){
    const dist = haversine(campus.lat, campus.lng, lt, ln);
    if(dist > radiusMeters){
      alert('Attendance can only be recorded from within MUBS premises.');
      return;
    }
  }
  submitForm.submit();
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/attendance/checkin_show.blade.php ENDPATH**/ ?>