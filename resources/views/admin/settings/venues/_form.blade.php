@php
    $v = $venue ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Venue Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $v?->name) }}" required />
    </div>
    <div class="col-md-6">
        <label class="form-label">Parent Building <small class="text-muted">(leave empty for a top-level
                building)</small></label>
        <select name="parent_id" class="form-select">
            <option value="">— None (Top-level Building) —</option>
            @foreach ($buildings as $building)
                <option value="{{ $building->id }}"
                    {{ old('parent_id', $v?->parent_id) == $building->id ? 'selected' : '' }}>
                    {{ $building->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <hr class="my-2">
        <p class="text-muted mb-2">
            <span class="ri ri-map-pin-line me-1"></span>
            <strong>Location Override</strong> — leave blank to use the
            <a href="{{ route('admin.settings.location.edit') }}">global school location</a>.
        </p>
    </div>
    <div class="col-md-4">
        <label class="form-label">Latitude</label>
        <input type="number" step="any" name="latitude" class="form-control"
            value="{{ old('latitude', $v?->latitude) }}" placeholder="e.g. 0.332931" />
    </div>
    <div class="col-md-4">
        <label class="form-label">Longitude</label>
        <input type="number" step="any" name="longitude" class="form-control"
            value="{{ old('longitude', $v?->longitude) }}" placeholder="e.g. 32.621927" />
    </div>
    <div class="col-md-4">
        <label class="form-label">Radius (meters)</label>
        <input type="number" name="radius_meters" class="form-control"
            value="{{ old('radius_meters', $v?->radius_meters) }}" min="10" max="5000"
            placeholder="e.g. 100" />
    </div>
    <div class="col-md-12">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="getLocationBtn">
            <span class="ri ri-map-pin-line me-1"></span> Use Current Location
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('getLocationBtn');
        if (btn) {
            btn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="ri ri-loader-4-line me-1"></span> Locating...';
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        document.querySelector('input[name="latitude"]').value = pos.coords
                            .latitude;
                        document.querySelector('input[name="longitude"]').value = pos.coords
                            .longitude;
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }, function(err) {
                        alert('Error getting location: ' + err.message);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                } else {
                    alert('Geolocation is not supported by this browser.');
                }
            });
        }
    });
</script>
