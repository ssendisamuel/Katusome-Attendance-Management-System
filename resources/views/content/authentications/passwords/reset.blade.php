@php
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reset Password')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
    <div class="position-relative">
        <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
            <div class="authentication-inner py-6">
                <div class="card p-md-7 p-1">
                    <div class="card-body mt-1">
                        <h4 class="mb-1">Set a new password</h4>
                        <p class="mb-5">Enter and confirm your new password.</p>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}" class="mb-5">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}" />
                            <div class="form-floating form-floating-outline mb-5 form-control-validation">
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Enter your email" value="{{ request()->email ?? old('email') }}" required
                                    {{ request()->email ? 'readonly' : '' }} />
                                <label for="email">Email</label>
                                @error('email')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-5 form-password-toggle form-control-validation">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" id="password" class="form-control" name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" required />
                                        <label for="password">New Password</label>
                                    </div>
                                    <span class="input-group-text cursor-pointer"><i
                                            class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-5 form-password-toggle form-control-validation">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" id="password_confirmation" class="form-control"
                                            name="password_confirmation"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password_confirmation" required />
                                        <label for="password_confirmation">Confirm Password</label>
                                    </div>
                                    <span class="input-group-text cursor-pointer"><i
                                            class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                                </div>
                            </div>
                            <button class="btn btn-primary d-grid w-100" type="submit">Reset Password</button>
                        </form>

                        <p class="text-center mb-1">
                            <a href="{{ route('login') }}">Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
