@extends('layouts/layoutMaster')

@section('title', 'My Schedule Series')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">My Schedule Series</h4>
        <a href="{{ route('lecturer.series.create') }}" class="btn btn-primary">Add Series</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Group</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($series as $s)
                        <tr>
                            <td>{{ $s->name }}</td>
                            <td>{{ optional($s->course)->code }}</td>
                            <td>{{ optional($s->group)->name }}</td>
                            <td>
                                <div>{{ $s->start_date ? $s->start_date->format('M d') : '' }} -
                                    {{ $s->end_date ? $s->end_date->format('M d') : '' }}</div>
                                <small class="text-muted">{{ $s->start_time ? $s->start_time->format('H:i') : '' }} -
                                    {{ $s->end_time ? $s->end_time->format('H:i') : '' }}</small>
                            </td>
                            <td>
                                @foreach ($s->days_of_week ?? [] as $d)
                                    <span class="badge bg-label-secondary">{{ ucfirst(substr($d, 0, 3)) }}</span>
                                @endforeach
                            </td>
                            <td class="text-end">
                                <a href="{{ route('lecturer.series.edit', $s) }}"
                                    class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                <button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal"
                                    data-bs-target="#generateModal-{{ $s->id }}">Generate</button>
                                <form action="{{ route('lecturer.series.destroy', $s) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-series"
                                        data-name="{{ $s->name }}"
                                        onclick="return confirm('Delete this series?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $series->links() }}</div>
    </div>

    @foreach ($series as $s)
        <!-- Per-series Generate Modal -->
        <div class="modal fade" id="generateModal-{{ $s->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Schedules: "{{ $s->name }}"</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('lecturer.series.generate-schedules', $s) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="overwrite-{{ $s->id }}" name="overwrite">
                                <label class="form-check-label" for="overwrite-{{ $s->id }}">Overwrite existing
                                    schedules for this series</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="skipOverlaps-{{ $s->id }}" name="skip_overlaps">
                                <label class="form-check-label" for="skipOverlaps-{{ $s->id }}">Skip if overlapping
                                    schedule exists</label>
                            </div>
                            <div class="alert alert-info mt-3">
                                Generates sessions for {{ implode(', ', $s->days_of_week ?? []) }} between
                                {{ $s->start_date ? $s->start_date->format('M d') : '' }} and
                                {{ $s->end_date ? $s->end_date->format('M d') : '' }}.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-label-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-success">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
