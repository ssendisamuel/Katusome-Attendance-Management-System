@extends('layouts/layoutMaster')

@section('title', 'Admin User Management')

@section('content')
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Admins</h5>
            <a href="{{ route('admin.admins.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Add New Admin
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        <tr>
                            <td>
                                <div class="d-flex justify-content-start align-items-center user-name">
                                    <div class="avatar-wrapper">
                                        <div class="avatar avatar-sm me-3">
                                            <span
                                                class="avatar-initial rounded-circle bg-label-primary">{{ substr($admin->name, 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $admin->name }}</span>
                                        <small class="text-muted">Admin</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('admin.admins.edit', $admin->id) }}"
                                        class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                    @if (auth()->id() !== $admin->id)
                                        <form action="{{ route('admin.admins.destroy', $admin->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this admin?');"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No admins found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $admins->links() }}
        </div>
    </div>
@endsection
