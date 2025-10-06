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
      window.Toast && window.Toast.fire({ icon: 'success', title: @json(session('success')) });
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
            <input type="number" step="0.000001" name="latitude" class="form-control" value="{{ old('latitude', $setting->latitude) }}" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Longitude</label>
            <input type="number" step="0.000001" name="longitude" class="form-control" value="{{ old('longitude', $setting->longitude) }}" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Radius (meters)</label>
            <input type="number" name="radius_meters" class="form-control" value="{{ old('radius_meters', $setting->radius_meters) }}" min="10" max="5000" required />
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
  </div>
</div>
@endsection