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
    <h4 class="mb-0">Record Attendance — {{ optional($schedule->course)->name }}
      ({{ optional($schedule->course)->code }})</h4>
    <span class="text-muted">
      {{ $schedule->start_at->format('H:i') }}–{{ $schedule->end_at->format('H:i') }}
      @if ($schedule->is_online)
        <span class="badge bg-label-info ms-1">Online</span>
      @else
        @ {{ $schedule->location }}
      @endif
    </span>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  @php
    $attStatus = $schedule->attendance_status ?? 'scheduled';
    $isClosed = $attStatus === 'closed';

    $isLateByThreshold = $schedule->late_at && now()->gt($schedule->late_at);
    $isLateByDefault = $attStatus === 'scheduled' && now()->gt($schedule->start_at->copy()->addMinutes(60));
    $isLate = $attStatus === 'late' || $isLateByThreshold || $isLateByDefault;
  @endphp

  @if ($isClosed)
    <div class="alert alert-danger mt-3">
      <i class="ri-error-warning-line me-1"></i>
      <strong>Attendance Disabled:</strong> This session has been manually closed or disabled for attendance
      recording.
    </div>
  @elseif($isLate)
    <div class="alert alert-warning mt-3">
      <i class="ri-time-line me-1"></i>
      <strong>Late Period:</strong> You are checking in after the deadline. Your attendance will be marked as
      <strong>Late</strong>.
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <div class="row g-4">
        <div class="col-12 col-md-6">
          <ul class="list-unstyled mb-4">
            <li><strong>Student:</strong> {{ $student->name }} ({{ $student->reg_no ?? $student->student_no }})
            </li>
            <li><strong>Course:</strong> {{ optional($schedule->course)->name }}
              ({{ optional($schedule->course)->code }})
            </li>
            @php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
            <li><strong>Lecturer:</strong>
              {{ $hasPivot && $schedule->relationLoaded('lecturers') && $schedule->lecturers && $schedule->lecturers->count() ? $schedule->lecturers->pluck('name')->implode(', ') : optional($schedule->lecturer)->name ?? '—' }}
            </li>
            <li><strong>Start Time:</strong> {{ $schedule->start_at->format('H:i') }}</li>
            <li><strong>Current Time:</strong> <span id="currentTime">—</span></li>
          </ul>
          <style>
            @keyframes scanner-scan {
              0% {
                top: 10%;
                opacity: 0;
              }

              10% {
                opacity: 1;
              }

              90% {
                opacity: 1;
              }

              100% {
                top: 90%;
                opacity: 0;
              }
            }

            .scanner-overlay-container {
              position: relative;
              width: 100%;
              height: 360px;
              /* Taller for better portrait fit */
              background: #000;
              border-radius: 16px;
              overflow: hidden;
              box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            }

            .scanner-line {
              position: absolute;
              width: 90%;
              left: 5%;
              height: 2px;
              background: #00d2ff;
              box-shadow: 0 0 8px #00d2ff, 0 0 16px #00d2ff;
              animation: scanner-scan 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            }

            .corner-bracket {
              position: absolute;
              width: 30px;
              height: 30px;
              border-color: #00d2ff;
              border-style: solid;
              box-shadow: 0 0 8px rgba(0, 210, 255, 0.5);
            }
          </style>
          <div id="scanner-container" class="scanner-overlay-container mb-3">
            <!-- Video Feed -->
            <video id="video" autoplay muted playsinline class="w-100 h-100"
              style="object-fit: cover; transform: scaleX(-1);"></video>

            <!-- Dark Backdrop Mask (SVG) -->
            <svg class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none; z-index: 10;"
              preserveAspectRatio="none">
              <defs>
                <mask id="scanner-mask">
                  <rect width="100%" height="100%" fill="white" />
                  <!-- Clear Cutout -->
                  <rect x="22%" y="15%" width="56%" height="70%" rx="30" ry="30" fill="black" />
                </mask>
              </defs>
              <rect width="100%" height="100%" fill="rgba(0,0,0,0.6)" mask="url(#scanner-mask)" />
            </svg>

            <!-- Active Scanner UI -->
            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
              style="pointer-events: none; z-index: 20;">
              <!-- Focus Box -->
              <div style="width: 56%; height: 70%; position: relative;">
                <!-- Corners -->
                <div class="corner-bracket"
                  style="top: 0; left: 0; border-width: 4px 0 0 4px; border-top-left-radius: 12px;"></div>
                <div class="corner-bracket"
                  style="top: 0; right: 0; border-width: 4px 4px 0 0; border-top-right-radius: 12px;">
                </div>
                <div class="corner-bracket"
                  style="bottom: 0; left: 0; border-width: 0 0 4px 4px; border-bottom-left-radius: 12px;">
                </div>
                <div class="corner-bracket"
                  style="bottom: 0; right: 0; border-width: 0 4px 4px 0; border-bottom-right-radius: 12px;">
                </div>

                <!-- Animated Scan Line -->
                <div class="scanner-line"></div>
              </div>
            </div>

            <!-- Instruction Text -->
            <div class="position-absolute bottom-0 w-100 text-center pb-4" style="z-index: 30;">
              <span
                class="badge bg-dark bg-opacity-75 text-white border border-secondary px-3 py-2 rounded-pill shadow-sm">
                <i class="ri-focus-3-line me-1 align-middle"></i> Align Face
              </span>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button id="captureBtn" class="btn btn-primary" {{ $isClosed ? 'disabled' : '' }}>Capture
              Attendance</button>
          </div>
          <!-- Fallback inline preview when modal is unavailable -->
          <div id="previewFallback" class="border rounded p-3 d-none mt-3">
            <img id="previewImgFallback" class="img-fluid rounded mb-2" alt="Captured selfie" />
            <div class="small text-muted mb-2">
              <div class="small text-muted mb-2">
                <div>Captured at: <span id="capturedAtFallback">—</span></div>
                @if ($schedule->is_online)
                  <div class="mt-2">
                    <label class="form-label fw-bold">Session Code (Required)</label>
                    <input type="text" id="fallbackAccessCode" class="form-control"
                      placeholder="Enter code shared by lecturer">
                  </div>
                @else
                  <div>Captured location: <span id="capturedLocFallback">Detecting…</span></div>
                  <div>Distance from MUBS: <span id="capturedDistFallback">—</span></div>
                  <div>Accuracy: <span id="capturedAccFallback">—</span></div>
                @endif
              </div>
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
                @if ($schedule->is_online)
                  <div class="mb-3">
                    <label class="form-label">Session Code (Required)</label>
                    <input type="text" id="modalAccessCode" class="form-control"
                      placeholder="Enter code shared by lecturer">
                  </div>
                @else
                  <div class="small text-muted mb-2">
                    <div>Captured at: <span id="capturedAt">—</span></div>
                    <div>Captured location: <span id="capturedLoc">Detecting…</span></div>
                    <div>Distance from MUBS: <span id="capturedDist">—</span></div>
                    <div>Accuracy: <span id="capturedAcc">—</span></div>
                  </div>
                @endif
              </div>
              <div class="modal-footer">
                <button id="confirmBtn" class="btn btn-success">Confirm Attendance</button>
                <button id="retakeBtnModal" class="btn btn-outline-secondary" data-bs-dismiss="modal">Retake
                  Photo</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <form id="submitForm" method="POST" action="{{ route('attendance.checkin.store') }}"
        enctype="multipart/form-data" class="d-none">
        @csrf
        <input type="hidden" name="schedule_id" value="{{ $schedule->id }}" />
        <input type="hidden" name="lat" id="latInput" />
        <input type="hidden" name="lng" id="lngInput" />
        <input type="hidden" name="accuracy" id="accuracyInput" />
        <input type="hidden" name="distance_meters" id="distanceInput" />
        <input type="hidden" name="access_code" id="accessCodeInput" />
        <input type="file" name="selfie" id="selfieInput" accept="image/*" />
      </form>
    </div>
    <div class="card-footer">
      @if ($existing)
        <div class="alert alert-info mb-0">Already marked: {{ ucfirst($existing->status) }} at
          {{ $existing->marked_at->format('H:i') }}</div>
      @endif
    </div>
  </div>

  <script>
    // Time updater
    function updateCurrentTime() {
      const el = document.getElementById('currentTime');
      const now = new Date();
      el.textContent = now.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    }
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();

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
      ok?.addEventListener('click', () => wrap.remove(), {
        once: true
      });
    }

    // Gating for confirm action based on location
    let locationReady = false;
    let withinRadius = false;
    let outsideNoticeShown = false;
    let outsideReminderTimer = null;
    const OUTSIDE_REMINDER_MS = 15000; // gentle reminder every 15s

    function startOutsideReminder() {
      if (outsideReminderTimer) return;
      outsideReminderTimer = setInterval(() => {
        const disabled = !locationReady || !withinRadius;
        // Stop reminding once user is within bounds or location is missing
        if (!disabled) {
          stopOutsideReminder();
          return;
        }
        const baseMsg = `Move within ${locationLabel} to record attendance.`;
        const distText = (pageDist && pageDist.textContent && pageDist.textContent !== '—') ?
          pageDist.textContent :
          (capturedDist && capturedDist.textContent && capturedDist.textContent !== '—') ?
          capturedDist.textContent :
          '';
        const text = distText ? `Outside ${locationLabel} (${distText}). ${baseMsg}` : baseMsg;
        if (Toast) {
          Toast.fire({
            icon: 'info',
            title: text
          });
        } else if (window.Swal && !Swal.isVisible()) {
          Swal.fire({
            icon: 'info',
            title: 'Outside allowed area',
            text,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        }
      }, OUTSIDE_REMINDER_MS);
    }

    function stopOutsideReminder() {
      if (outsideReminderTimer) {
        clearInterval(outsideReminderTimer);
        outsideReminderTimer = null;
      }
    }

    function updateConfirmState() {
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
        const baseMsg = `Attendance can only be recorded from within ${locationLabel}.`;
        // Try to include current distance in the message if available
        const distText = (pageDist && pageDist.textContent && pageDist.textContent !== '—') ?
          pageDist.textContent :
          (capturedDist && capturedDist.textContent && capturedDist.textContent !== '—') ?
          capturedDist.textContent :
          '';
        const msg = distText ? `${baseMsg} Outside ${locationLabel} (${distText}).` : baseMsg;
        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Outside allowed area',
            text: msg,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        } else {
          showInlineModal('Outside allowed area', msg);
        }
        outsideNoticeShown = true;
      }
      // Start/stop gentle periodic reminders while outside bounds
      if (locationReady && !withinRadius) startOutsideReminder();
      else stopOutsideReminder();
    }

    // Toast helper from global (defined in main.js), fallback if missing
    const Toast = window.Toast || (window.Swal ? Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true
    }) : null);

    // Geofence constants — venue-specific or global fallback
    <?php
    $_geoLat = 0.332931;
    $_geoLng = 32.621927;
    $_geoRadius = 150;
    $_venueName = null;
    if (isset($setting) && $setting) {
        $_geoLat = $setting->latitude ?? $_geoLat;
        $_geoLng = $setting->longitude ?? $_geoLng;
        $_geoRadius = $setting->radius_meters ?? $_geoRadius;
    }
    if (!empty($schedule->venue_id)) {
        $venue = $schedule->venue;
        if ($venue) {
            $vc = $venue->getLocationCoordinates();
            $_geoLat = $vc['latitude'] ?? $_geoLat;
            $_geoLng = $vc['longitude'] ?? $_geoLng;
            $_geoRadius = $vc['radius_meters'] ?? $_geoRadius;
            $_venueName = $venue->fullName();
        }
    }
    ?>
    const campus = {
      lat: <?= $_geoLat ?>,
      lng: <?= $_geoLng ?>
    };
    const radiusMeters = <?= $_geoRadius ?>;
    const venueName = <?= json_encode($_venueName) ?>;
    const locationLabel = venueName || 'MUBS premises';
    console.log('[Check-In Show] Geofence config', {
      campus,
      radiusMeters,
      locationLabel
    });

    function haversine(lat1, lon1, lat2, lon2) {
      const R = 6371000;
      const toRad = x => x * Math.PI / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
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
        const opts = {
          enableHighAccuracy: true,
          maximumAge: 0,
          timeout: 10000
        };
        const watchId = navigator.geolocation.watchPosition(
          (pos) => {
            bestPos = pos;
            const acc = pos?.coords?.accuracy ?? Infinity;
            if (typeof acc === 'number' && isFinite(acc)) {
              // Update inline status while sampling
              if (pageAcc) pageAcc.textContent = `${Math.round(acc)}m`;
              if (pageLocStatus) pageLocStatus.textContent = acc <= desiredAccuracy ?
                'Location acquired.' :
                `Getting a precise location… (${Math.round(acc)}m)`;
            }
            if (!settled && acc <= desiredAccuracy) {
              settled = true;
              navigator.geolocation.clearWatch(watchId);
              resolve({
                pos,
                timedOut: false
              });
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
              resolve({
                pos: bestPos,
                timedOut: true
              });
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
        const constraints = {
          video: {
            facingMode: {
              ideal: 'user'
            }
          },
          audio: false
        };
        let stream = await navigator.mediaDevices.getUserMedia(constraints);
        if (!stream || !stream.getVideoTracks().length) throw new Error('No video track');
        video.srcObject = stream;
        await video.play();
      } catch (e) {
        // Fallback for Edge or browsers with limited constraint support
        try {
          const fallbackStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false
          });
          video.srcObject = fallbackStream;
          await video.play();
        } catch (err) {
          if (window.Swal) {
            Swal.fire({
              icon: 'error',
              title: 'Camera not available',
              text: 'Please allow camera access or try another device/browser.',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          } else {
            showInlineModal('Camera not available',
              'Please allow camera access or try another device/browser.');
          }
        }
      }
    }
    // Prompt for permissions on load, then initialize camera and location.
    function promptPermissionsAndInit() {
      if (window.Swal) {
        Swal.fire({
          icon: 'info',
          title: 'Permissions Required',
          html: 'Please allow <strong>camera</strong> and <strong>location</strong> access to record attendance.<br/>On iOS Safari, ensure the site has camera access in Settings.',
          confirmButtonText: 'OK, proceed',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        }).then(() => {
          // Initialize camera using this user gesture to satisfy Safari
          if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            initCamera();
          } else {
            if (window.Swal) {
              Swal.fire({
                icon: 'error',
                title: 'Camera unsupported',
                text: 'Your browser does not support camera access.',
                customClass: {
                  confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
              });
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
      const arr = dataUrl.split(','),
        mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]);
      let n = bstr.length;
      const u8arr = new Uint8Array(n);
      while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
      }
      return new File([u8arr], filename, {
        type: mime
      });
    }

    function captureImage() {
      // Ensure video has current frame data (fixes black captures on some browsers)
      const ensureReady = () => new Promise(resolve => {
        if (video.readyState >= 2 && video.videoWidth > 0) return resolve();
        video.addEventListener('loadeddata', () => resolve(), {
          once: true
        });
      });
      ensureReady().then(() => {
        const canvas = document.createElement('canvas');
        // Calculate scale to limit max dimension (e.g. 1280px)
        const MAX_DIMENSION = 1280;
        let width = video.videoWidth;
        let height = video.videoHeight;

        if (width > height) {
          if (width > MAX_DIMENSION) {
            height *= MAX_DIMENSION / width;
            width = MAX_DIMENSION;
          }
        } else {
          if (height > MAX_DIMENSION) {
            width *= MAX_DIMENSION / height;
            height = MAX_DIMENSION;
          }
        }

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, width, height);

        // Compress to JPEG with 0.7 quality
        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);

        previewImg.src = dataUrl;
        if (previewImgFallback) previewImgFallback.src = dataUrl;
        const file = dataURLtoFile(dataUrl, 'selfie.jpg');
        const dt = new DataTransfer();
        dt.items.add(file);
        selfieInput.files = dt.files;
      });
    }



    const isOnline = {{ $schedule->is_online ? 'true' : 'false' }};

    async function getLocation() {
      if (isOnline) {
        // Skip location for online classes
        locationReady = true;
        withinRadius = true;
        updateConfirmState();
        return;
      }

      capturedLoc.textContent = 'Detecting…';
      if (capturedAcc) capturedAcc.textContent = '—';
      if (capturedAccFallback) capturedAccFallback.textContent = '—';
      if (pageAcc) pageAcc.textContent = '—';
      if (pageLocStatus) {
        pageLocStatus.textContent = 'Getting a precise location…';
        pageLocStatus.classList.remove('text-danger');
      }
      // Reset outside notice so we can notify again for a new fix
      outsideNoticeShown = false;
      stopOutsideReminder();
      if (!navigator.geolocation) {
        capturedLoc.textContent = 'Unavailable';
        if (pageLocStatus) {
          pageLocStatus.textContent = 'Geolocation unsupported on this device.';
          pageLocStatus.classList.add('text-danger');
        }
        locationReady = false;
        withinRadius = false;
        updateConfirmState();
        return;
      }
      try {
        const {
          pos,
          timedOut
        } = await getAccuratePosition(100, 15000);
        const {
          latitude,
          longitude,
          accuracy
        } = pos.coords;
        latInput.value = latitude;
        lngInput.value = longitude;
        if (accuracyInput) accuracyInput.value = typeof accuracy === 'number' ? Math.round(accuracy) : '';
        capturedLoc.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        if (capturedLocFallback) capturedLocFallback.textContent =
          `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
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
        if (capturedAcc) capturedAcc.textContent = (typeof accuracy === 'number') ? `${Math.round(accuracy)}m` :
          '—';
        if (capturedAccFallback) capturedAccFallback.textContent = (typeof accuracy === 'number') ?
          `${Math.round(accuracy)}m` : '—';
        if (pageAcc) pageAcc.textContent = (typeof accuracy === 'number') ? `${Math.round(accuracy)}m` : '—';
        if (pageLocStatus) {
          const usingCoarse = timedOut && typeof accuracy === 'number' && accuracy > 100;
          pageLocStatus.textContent = usingCoarse ?
            `Using less precise location (~${Math.round(accuracy)}m)` :
            'Location acquired.';
          // If outside radius, reflect that immediately in status for clarity
          if (dist > radiusMeters) {
            pageLocStatus.textContent = `Outside ${locationLabel} (${distRounded}m).`;
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
        console.log('[Check-In Show] Located', {
          latitude,
          longitude,
          dist,
          accuracy,
          timedOut
        });
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
        if (pageDist) {
          pageDist.textContent = '—';
          pageDist.classList.remove('text-success', 'text-danger');
        }
        if (pageLocStatus) {
          pageLocStatus.textContent = 'Location permission denied or unavailable.';
          pageLocStatus.classList.add('text-danger');
        }
        locationReady = false;
        withinRadius = false;
        updateConfirmState();
      }
    }

    const scannerContainer = document.getElementById('scanner-container');

    captureBtn.addEventListener('click', (e) => {
      e.preventDefault();
      console.log('Capture button clicked');
      try {
        captureImage();
        const ts = new Date().toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
        if (capturedAt) capturedAt.textContent = ts;
        if (capturedAtFallback) capturedAtFallback.textContent = ts;
        console.log('Image captured, getting location...');
        getLocation();
        // Show modal with preview
        if (previewModal) {
          console.log('Showing Bootstrap modal');
          previewModal.show();
        } else if (previewFallback) {
          console.log('Showing fallback preview');
          previewFallback.classList.remove('d-none');
        } else {
          console.error('No preview modal or fallback found');
          alert('Error: Could not show capture preview.');
        }
        if (retakeBtn) retakeBtn.classList.remove('d-none');
        // Hide capture button after first capture to avoid duplicate actions
        captureBtn.classList.add('d-none');
        // Hide scanner container (video + overlay) while previewing the captured photo
        if (scannerContainer) scannerContainer.classList.add('d-none');
      } catch (err) {
        console.error('Error in capture handler:', err);
        alert('An error occurred while capturing: ' + err.message);
      }
    });

    if (retakeBtn) {
      retakeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Switch back to webcam view and allow a new capture
        if (previewModal) {
          previewModal.hide();
        }
        if (previewFallback) {
          previewFallback.classList.add('d-none');
        }
        if (scannerContainer) scannerContainer.classList.remove('d-none');
        captureBtn.classList.remove('d-none');
        retakeBtn.classList.add('d-none');
      });
    }

    // Retake from modal closes modal and refreshes capture
    if (retakeBtnModal) {
      retakeBtnModal.addEventListener('click', (e) => {
        e.preventDefault();
        // Close modal and switch to webcam for a fresh capture
        if (previewModal) {
          previewModal.hide();
        }
        if (previewFallback) {
          previewFallback.classList.add('d-none');
        }
        if (scannerContainer) scannerContainer.classList.remove('d-none');
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
        if (scannerContainer) scannerContainer.classList.remove('d-none');
        captureBtn.classList.remove('d-none');
        if (retakeBtn) retakeBtn.classList.add('d-none');
      });
    }

    // Retake from fallback preview: return to webcam for a fresh capture
    if (retakeBtnFallback) {
      retakeBtnFallback.addEventListener('click', (e) => {
        e.preventDefault();
        if (previewModal) {
          previewModal.hide();
        }
        if (previewFallback) {
          previewFallback.classList.add('d-none');
        }
        if (scannerContainer) scannerContainer.classList.remove('d-none');
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
          Swal.fire({
            icon: 'info',
            title: 'Enable location',
            text: msg,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        } else {
          showInlineModal('Enable location', msg);
        }
        return;
      }
      const lt = parseFloat(latInput.value),
        ln = parseFloat(lngInput.value);
      if (isFinite(lt) && isFinite(ln)) {
        const dist = haversine(campus.lat, campus.lng, lt, ln);
        const distRounded = Math.round(dist);
        console.log('[Check-In Show] Confirm distance check', {
          lt,
          ln,
          dist,
          radiusMeters,
          campus
        });
        if (dist > radiusMeters) {
          if (window.Swal) {
            Swal.fire({
              icon: 'warning',
              title: 'Outside allowed area',
              text: `You are not within ${locationLabel} (${distRounded}m away). Attendance can only be recorded from the assigned venue.`,
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          } else {
            showInlineModal('Outside allowed area',
              `You are not within ${locationLabel} (${distRounded}m away). Attendance can only be recorded from the assigned venue.`
            );
          }
        }
      }

      // Online Code Check
      if (isOnline) {
        const codeInput = document.getElementById('modalAccessCode');
        const fallbackInput = document.getElementById('fallbackAccessCode');
        const hiddenInput = document.getElementById('accessCodeInput');

        let code = '';
        if (codeInput && codeInput.offsetParent !== null) {
          code = codeInput.value.trim();
        } else if (fallbackInput && fallbackInput.offsetParent !== null) {
          code = fallbackInput.value.trim();
        }

        if (!code) {
          if (window.Swal) {
            Swal.fire({
              icon: 'warning',
              title: 'Code Required',
              text: 'Please enter the Session Code shared by your lecturer.'
            });
          } else {
            alert('Please enter the Session Code.');
          }
          return;
        }
        hiddenInput.value = code;
      }

      if (window.Swal) {
        const originalAction = submitForm.action;
        const formData = new FormData(submitForm);

        Swal.fire({
          icon: 'question',
          title: 'Confirm attendance?',
          text: 'Make sure the photo and location are correct.',
          showCancelButton: true,
          confirmButtonText: 'Yes, confirm',
          cancelButtonText: 'Cancel',
          showLoaderOnConfirm: true,
          customClass: {
            confirmButton: 'btn btn-success me-2',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
          preConfirm: async () => {
            try {
              const response = await fetch(originalAction, {
                method: 'POST',
                body: formData,
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });

              // Try to parse JSON if available
              let data = null;
              const contentType = response.headers.get('content-type') || '';
              if (contentType.includes('application/json')) {
                data = await response.json().catch(() => null);
              }

              if (!response.ok && !response.redirected) {
                const errorMsg = (data && data.message) ? data.message :
                  'Submission failed';
                throw new Error(errorMsg);
              }

              return {
                response,
                data
              };

            } catch (error) {
              Swal.showValidationMessage(`Request failed: ${error.message}`);
            }
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then(async (result) => {
          if (result.isConfirmed) {
            const {
              response,
              data
            } = result.value;

            if (response.redirected) {
              const title = (data && data.message) ? data.message :
                'Attendance recorded!';
              await Swal.fire({
                icon: 'success',
                title,
                timer: 1500,
                showConfirmButton: false
              });
              if (previewModal) previewModal.hide();
              if (previewFallback) previewFallback.classList.add('d-none');
              // Keep buttons hidden/disabled during redirect
              window.location.href = response.url;
            } else if (response.ok) {
              const redirectUrl = (data && data.redirect) ? data.redirect : (data && data
                .url) ? data.url : null;
              const title = (data && data.message) ? data.message :
                'Attendance recorded!';

              await Swal.fire({
                icon: 'success',
                title,
                timer: 1500,
                showConfirmButton: false
              });

              if (previewModal) previewModal.hide();
              if (previewFallback) previewFallback.classList.add('d-none');

              if (redirectUrl) {
                window.location.href = redirectUrl;
              } else {
                // Fallback if no redirect - restore state (unlikely for this flow)
                if (captureBtn) captureBtn.classList.remove('d-none');
              }
            }
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

  {{-- Data Collection Disclaimer --}}
  <div class="alert alert-info mt-4" role="alert">
    <i class="ri-information-line me-1"></i>
    <strong>Privacy Notice:</strong> The data collected through this attendance system (including photos and location) is
    used solely for academic purposes and attendance verification.
  </div>
@endsection
