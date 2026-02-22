@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Account Settings - Profile')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Account Settings /</span> Profile
        </h4>

        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i class="ri-user-line me-1"></i>
                            Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('password.change.edit') }}"><i
                                class="ri-lock-line me-1"></i> Security</a></li>
                </ul>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card mb-4">
                    <h5 class="card-header">Profile Details</h5>
                    <!-- Account -->
                    <form id="formAccountSettings" method="POST" action="{{ route('profile.update') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="card-body">
                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                @php
                                    $hasAvatar = $user && !empty($user->avatar_url);
                                    $name = $user->name ?? 'User';
                                    $initials = collect(explode(' ', $name))
                                        ->map(fn($p) => mb_substr($p, 0, 1))
                                        ->implode('');
                                    $initials = mb_strtoupper(mb_substr($initials, 0, 2));
                                    $defaultAvatar = $hasAvatar ? $user->avatar_url : '';
                                @endphp

                                <div id="avatar-preview-wrapper">
                                    @if ($hasAvatar)
                                        <img src="{{ $user->avatar_url }}" alt="user-avatar" class="d-block rounded"
                                            height="100" width="100" id="uploadedAvatar" />
                                    @else
                                        <div class="avatar avatar-xl" style="height: 100px; width: 100px;"
                                            id="uploadedAvatar">
                                            <span
                                                class="avatar-initial rounded bg-label-primary d-flex align-items-center justify-content-center h-100 w-100 display-4">
                                                {{ $initials }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="button-wrapper">
                                    <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                                        <span class="d-none d-sm-block">Upload new photo</span>
                                        <i class="ri-upload-2-line d-block d-sm-none"></i>
                                        <input type="file" id="upload" class="account-file-input" hidden
                                            name="avatar" accept="image/png, image/jpeg" />
                                    </label>
                                    <button type="button" class="btn btn-outline-secondary account-image-reset mb-4">
                                        <i class="ri-refresh-line d-block d-sm-none"></i>
                                        <span class="d-none d-sm-block">Reset</span>
                                    </button>
                                    <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 2MB</p>
                                </div>
                            </div>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input class="form-control" type="text" id="name" name="name"
                                        value="{{ old('name', $user->name) }}" autofocus required />
                                    @error('name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input class="form-control" type="text" id="email" name="email"
                                        value="{{ old('email', $user->email) }}" placeholder="john.doe@example.com"
                                        required />
                                    @error('email')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if ($student)
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Student Number</label>
                                        <input type="text" class="form-control" value="{{ $student->student_no }}"
                                            disabled />
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Registration Number</label>
                                        <input type="text" class="form-control" value="{{ $student->reg_no }}"
                                            disabled />
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="phoneNumber">Phone Number</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text">UG (+256)</span>
                                            <input type="text" id="phoneNumber" name="phone" class="form-control"
                                                value="{{ old('phone', $student->phone) }}" placeholder="700 000 000" />
                                        </div>
                                        @error('phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" name="gender" class="form-select">
                                            @php $g = old('gender', $student->gender); @endphp
                                            <option value="" {{ $g === null ? 'selected' : '' }}>Select</option>
                                            <option value="male" {{ $g === 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ $g === 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ $g === 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="program" class="form-label">Program</label>
                                        <input type="text" class="form-control" id="program"
                                            value="{{ optional($student->program)->name }}" disabled />
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="group" class="form-label">Group</label>
                                        <input type="text" class="form-control" id="group"
                                            value="{{ optional($student->group)->name }}" disabled />
                                    </div>
                                @endif

                                @if ($lecturer)
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="phoneNumber">Phone Number</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text">UG (+256)</span>
                                            <input type="text" id="phoneNumber" name="phone" class="form-control"
                                                value="{{ old('phone', $lecturer->phone) }}" placeholder="700 000 000" />
                                        </div>
                                        @error('phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary me-2">Save changes</button>
                                <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                            </div>
                        </div>
                    </form>
                    <!-- /Account -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add the modal at the end of content -->
    <div class="modal fade" id="cropImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropImageModalLabel">Crop Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="imageToCrop" src="" alt="Picture">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cropButton">Crop & Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <style>
        .img-container {
            max-width: 100%;
            max-height: 500px;
        }

        #imageToCrop {
            display: block;
            max-width: 100%;
        }
    </style>
@endsection

@section('page-script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(e) {
            (function() {
                const fileInput = document.querySelector('.account-file-input');
                const resetFileInput = document.querySelector('.account-image-reset');
                const imageToCrop = document.getElementById('imageToCrop');
                const cropModalEl = document.getElementById('cropImageModal');
                let cropModal;
                let cropper;

                if (cropModalEl) {
                    cropModal = new bootstrap.Modal(cropModalEl);
                }

                // Preview element logic
                let accountUserImage = document.getElementById('uploadedAvatar');
                const previewWrapper = document.getElementById('avatar-preview-wrapper');
                // Store original src to reset
                const originalImageSrc = accountUserImage && accountUserImage.tagName === 'IMG' ?
                    accountUserImage.src : '';

                if (fileInput) {
                    fileInput.onchange = () => {
                        if (fileInput.files && fileInput.files.length > 0) {
                            const file = fileInput.files[0];
                            const reader = new FileReader();

                            reader.onload = function(e) {
                                // Set image to crop
                                imageToCrop.src = e.target.result;
                                // Show modal
                                if (cropModal) cropModal.show();
                            };
                            reader.readAsDataURL(file);
                        }
                    };

                    resetFileInput.onclick = () => {
                        fileInput.value = '';
                        if (accountUserImage && accountUserImage.tagName === 'IMG') {
                            accountUserImage.src = originalImageSrc;
                        } else {
                            location.reload();
                        }
                    };

                    // Handle Modal Events for Cropper
                    if (cropModalEl) {
                        cropModalEl.addEventListener('shown.bs.modal', function() {
                            cropper = new Cropper(imageToCrop, {
                                aspectRatio: 1,
                                viewMode: 1,
                                autoCropArea: 1,
                            });
                        });

                        cropModalEl.addEventListener('hidden.bs.modal', function() {
                            if (cropper) {
                                cropper.destroy();
                                cropper = null;
                            }
                            // If we closed the modal without cropping, we should clear the input
                            // so that selecting the same file again triggers 'change'.
                            // But we can't easily distinguish 'Cancel' from 'Backdrop click',
                            // unless we set a flag on Crop button.
                            // For now, if the fileInput still has files, we might want to clear it if logical.
                            // But let's leave it; user can click reset.
                        });
                    }

                    document.getElementById('cropButton').addEventListener('click', function() {
                        if (cropper) {
                            cropper.getCroppedCanvas({
                                width: 300,
                                height: 300,
                            }).toBlob(function(blob) {
                                // Create a new file from the blob
                                // We need to match the name/type
                                const croppedFile = new File([blob], 'avatar.jpg', {
                                    type: 'image/jpeg'
                                });

                                // Update the file input
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(croppedFile);
                                fileInput.files = dataTransfer.files;

                                // Update Preview
                                const url = window.URL.createObjectURL(blob);

                                // If current element is not img (i.e. div with initials), replace it
                                if (!accountUserImage || accountUserImage.tagName !== 'IMG') {
                                    previewWrapper.innerHTML =
                                        `<img src="${url}" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />`;
                                    accountUserImage = document.getElementById(
                                    'uploadedAvatar');
                                } else {
                                    accountUserImage.src = url;
                                }

                                // Hide Modal
                                cropModal.hide();
                            }, 'image/jpeg');
                        }
                    });
                }
            })();
        });
    </script>
@endsection
