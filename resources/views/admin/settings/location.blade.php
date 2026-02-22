@extends('layouts/contentNavbarLayout')

@section('title', 'Location Settings')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Admin Settings / Location</h4>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <script>
                window.Toast && window.Toast.fire({
                    icon: 'success',
                    title: @json(session('success'))
                });
            </script>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.location.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" step="any" name="latitude" class="form-control"
                                value="{{ old('latitude', $setting->latitude) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" step="any" name="longitude" class="form-control"
                                value="{{ old('longitude', $setting->longitude) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Radius (meters)</label>
                            <input type="number" name="radius_meters" class="form-control"
                                value="{{ old('radius_meters', $setting->radius_meters) }}" min="10" max="5000"
                                required />
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="getLocationBtn">
                                <span class="icon-base ri ri-map-pin-line me-1"></span> Use Current Location
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-muted">These values control geofencing for student attendance check-in.</p>
            <a href="{{ route('admin.settings.venues.index') }}" class="btn btn-outline-primary">
                <span class="ri ri-building-line me-1"></span> Manage Venues
            </a>
        </div>
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
                        btn.innerHTML =
                            '<span class="icon-base ri ri-loader-4-line me-1"></span> Locating...';

                        navigator.geolocation.getCurrentPosition(function(position) {
                            document.querySelector('input[name="latitude"]').value = position.coords
                                .latitude;
                            document.querySelector('input[name="longitude"]').value = position
                                .coords.longitude;
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            // Optional: Show toast or small alert
                        }, function(error) {
                            alert('Error getting location: ' + error.message);
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                    } else {
                        alert("Geolocation is not supported by this browser.");
                    }
                });
            }
        });
    </script>
@endsection
