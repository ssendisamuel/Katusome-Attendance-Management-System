@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Venue')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-4">
            <h4 class="fw-bold mb-0">Edit Venue: {{ $venue->name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.settings.location.edit') }}">Location Settings</a>
                    </li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.settings.venues.index') }}">Venues</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.venues.update', $venue) }}">
                    @csrf @method('PUT')
                    @include('admin.settings.venues._form', ['venue' => $venue])
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="ri ri-save-line me-1"></span> Update Venue
                        </button>
                        <a href="{{ route('admin.settings.venues.index') }}"
                            class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
