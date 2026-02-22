@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Account Settings - Security')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Account Settings /</span> Security
        </h4>

        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item"><a class="nav-link" href="{{ route('profile.show') }}"><i
                                class="ri-user-line me-1"></i> Profile</a></li>
                    <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i
                                class="ri-lock-line me-1"></i> Security</a></li>
                </ul>

                @if (auth()->user() && auth()->user()->must_change_password)
                    <div class="alert alert-warning mb-4" role="alert">
                        <i class="ri-alert-line me-2"></i> For your security, you must change your password before accessing
                        other pages.
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Change Password -->
                <div class="card mb-4">
                    <h5 class="card-header">Change Password</h5>
                    <div class="card-body">
                        <form id="formAccountSettings" method="POST" action="{{ route('password.change.update') }}">
                            @csrf
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label" for="current_password">Current Password</label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control" type="password" name="current_password"
                                            id="current_password" autocomplete="current-password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                        <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                                    </div>
                                    @error('current_password')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label" for="newPassword">New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control" type="password" id="newPassword" name="password"
                                            autocomplete="new-password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                        <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                                    </div>
                                    @error('password')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label" for="confirmPassword">Confirm New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control" type="password" name="password_confirmation"
                                            id="confirmPassword" autocomplete="new-password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                        <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                                    </div>
                                    @error('password_confirmation')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-4">
                                    <p class="fw-semibold mt-2">Password Requirements:</p>
                                    <ul class="ps-3 mb-0">
                                        <li class="mb-1" id="req-length">Minimum 8 characters long</li>
                                        <li class="mb-1" id="req-case">At least one uppercase & one lowercase character
                                        </li>
                                        <li class="mb-1" id="req-number">At least one number</li>
                                        <li class="mb-1" id="req-symbol">At least one symbol</li>
                                    </ul>
                                </div>
                                <div class="col-12 mt-1">
                                    <button type="submit" id="submitBtn" class="btn btn-primary me-2">Save changes</button>
                                    <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!--/ Change Password -->

                <!-- Password Reset by Email -->
                <div class="card mb-4">
                    <h5 class="card-header">Forgot Password?</h5>
                    <div class="card-body">
                        <p>If you have forgotten your current password, you can request a password reset link to be sent to
                            your email address.</p>
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <button type="submit" class="btn btn-outline-primary">Send Password Reset Link</button>
                        </form>
                    </div>
                </div>
                <!--/ Password Reset by Email -->

            </div>
        </div>
    </div>
@endsection


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formAccountSettings');
        const newPassInput = document.getElementById('newPassword');
        const confirmPassInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');

        const reqLength = document.getElementById('req-length');
        const reqCase = document.getElementById('req-case');
        const reqNumber = document.getElementById('req-number');
        const reqSymbol = document.getElementById('req-symbol');

        // Regex rules
        const rules = {
            length: /.{8,}/,
            mixedCase: /(?=.*[a-z])(?=.*[A-Z])/,
            number: /[0-9]/,
            symbol: /[^a-zA-Z0-9\s]/ // Matches any non-alphanumeric char (symbols)
        };

        function validatePassword() {
            const val = newPassInput.value;
            let isValid = true;

            // Length
            if (rules.length.test(val)) {
                reqLength.classList.remove('text-danger');
                reqLength.classList.add('text-success');
                reqLength.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> Minimum 8 characters long';
            } else {
                reqLength.classList.remove('text-success');
                reqLength.classList.add('text-danger');
                reqLength.innerHTML = '<i class="ri-close-circle-line me-1"></i> Minimum 8 characters long';
                isValid = false;
            }

            // Mixed Case
            if (rules.mixedCase.test(val)) {
                reqCase.classList.remove('text-danger');
                reqCase.classList.add('text-success');
                reqCase.innerHTML =
                    '<i class="ri-checkbox-circle-line me-1"></i> At least one uppercase & one lowercase character';
            } else {
                reqCase.classList.remove('text-success');
                reqCase.classList.add('text-danger');
                reqCase.innerHTML =
                    '<i class="ri-close-circle-line me-1"></i> At least one uppercase & one lowercase character';
                isValid = false;
            }

            // Number
            if (rules.number.test(val)) {
                reqNumber.classList.remove('text-danger');
                reqNumber.classList.add('text-success');
                reqNumber.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> At least one number';
            } else {
                reqNumber.classList.remove('text-success');
                reqNumber.classList.add('text-danger');
                reqNumber.innerHTML = '<i class="ri-close-circle-line me-1"></i> At least one number';
                isValid = false;
            }

            // Symbol
            if (rules.symbol.test(val)) {
                reqSymbol.classList.remove('text-danger');
                reqSymbol.classList.add('text-success');
                reqSymbol.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> At least one symbol';
            } else {
                reqSymbol.classList.remove('text-success');
                reqSymbol.classList.add('text-danger');
                reqSymbol.innerHTML = '<i class="ri-close-circle-line me-1"></i> At least one symbol';
                isValid = false;
            }

            return isValid;
        }

        function validateMatch() {
            const pass = newPassInput.value;
            const conf = confirmPassInput.value;
            const inputGroup = confirmPassInput.closest('.input-group');

            // Remove existing match error if any
            let feedback = inputGroup.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback-custom')) {
                feedback.remove();
                confirmPassInput.classList.remove('is-invalid');
            }

            if (conf && pass !== conf) {
                confirmPassInput.classList.add('is-invalid');
                const div = document.createElement('div');
                div.className = 'invalid-feedback-custom text-danger small mt-1';
                div.innerText = 'Passwords do not match.';
                inputGroup.parentNode.insertBefore(div, inputGroup.nextSibling);
                return false;
            } else if (conf && pass === conf) {
                confirmPassInput.classList.remove('is-invalid');
                confirmPassInput.classList.add('is-valid');
                return true;
            }

            if (!conf) {
                confirmPassInput.classList.remove('is-invalid');
                confirmPassInput.classList.remove('is-valid');
                return false;
            }

            return false;
        }

        function updateState() {
            const isPassValid = validatePassword();
            const isMatchValid = validateMatch();
            // We enable button only if everything is valid
            submitBtn.disabled = !(isPassValid && isMatchValid);
        }

        if (newPassInput && confirmPassInput && submitBtn) {
            newPassInput.addEventListener('input', updateState);
            confirmPassInput.addEventListener('input', updateState);

            // Initial check
            updateState();
        }
    });
</script>
