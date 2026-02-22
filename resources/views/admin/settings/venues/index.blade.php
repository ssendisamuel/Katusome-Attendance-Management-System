@extends('layouts/contentNavbarLayout')

@section('title', 'Manage Venues')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Venues</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.location.edit') }}">Location
                                Settings</a></li>
                        <li class="breadcrumb-item active">Venues</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('admin.settings.venues.create') }}" class="btn btn-primary">
                <span class="ri ri-add-line me-1"></span> Add Venue
            </a>
        </div>

        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.Toast && window.Toast.fire({
                        icon: 'success',
                        title: @json(session('success'))
                    });
                });
            </script>
        @endif

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label mb-1"><span class="ri ri-search-line me-1"></span>Search</label>
                        <input type="text" id="venueSearch" class="form-control"
                            placeholder="Search buildings or rooms…">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><span class="ri ri-building-line me-1"></span>Building</label>
                        <select id="buildingFilter" class="form-select">
                            <option value="">All Buildings</option>
                            @foreach ($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}
                                    ({{ $b->children->count() }} rooms)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1"><span class="ri ri-map-pin-line me-1"></span>Coordinates</label>
                        <select id="coordsFilter" class="form-select">
                            <option value="">All</option>
                            <option value="has">Has own coordinates</option>
                            <option value="inherit">Inherits coordinates</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-auto">
                <span class="badge bg-label-primary fs-6 px-3 py-2">
                    <span class="ri ri-building-line me-1"></span>
                    {{ $buildings->count() }} Buildings
                </span>
            </div>
            <div class="col-auto">
                <span class="badge bg-label-info fs-6 px-3 py-2">
                    <span class="ri ri-door-open-line me-1"></span>
                    {{ $buildings->sum(fn($b) => $b->children->count()) }} Rooms
                </span>
            </div>
            <div class="col-auto" id="filterCount" style="display:none;">
                <span class="badge bg-label-warning fs-6 px-3 py-2">
                    <span class="ri ri-filter-line me-1"></span>
                    <span id="visibleCount">0</span> visible
                </span>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="venuesTable">
                    <thead>
                        <tr>
                            <th>Building / Room</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Radius (m)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($buildings as $building)
                            {{-- Building row --}}
                            <tr class="table-light venue-row building-row" data-building-id="{{ $building->id }}"
                                data-name="{{ strtolower($building->name) }}"
                                data-has-coords="{{ $building->latitude ? '1' : '0' }}" data-type="building">
                                <td>
                                    <span class="toggle-rooms" data-building="{{ $building->id }}"
                                        style="cursor:pointer; display:inline-block; width:20px;" title="Toggle rooms">
                                        @if ($building->children->count())
                                            <span class="ri ri-arrow-down-s-line"></span>
                                        @endif
                                    </span>
                                    <strong>{{ $building->name }}</strong>
                                    @if ($building->children->count())
                                        <span class="badge bg-label-info ms-1">{{ $building->children->count() }}
                                            rooms</span>
                                    @endif
                                </td>
                                <td>{{ $building->latitude ?? '—' }}</td>
                                <td>{{ $building->longitude ?? '—' }}</td>
                                <td>{{ $building->radius_meters ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.settings.venues.edit', $building) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                        <span class="ri ri-pencil-line"></span>
                                    </a>
                                    <form action="{{ route('admin.settings.venues.destroy', $building) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Delete {{ $building->name }} and all its rooms?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                            <span class="ri ri-delete-bin-line"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            {{-- Room rows --}}
                            @foreach ($building->children as $room)
                                <tr class="venue-row room-row room-of-{{ $building->id }}"
                                    data-building-id="{{ $building->id }}"
                                    data-name="{{ strtolower($building->name . ' ' . $room->name) }}"
                                    data-has-coords="{{ $room->latitude ? '1' : '0' }}" data-type="room">
                                    <td class="ps-5">
                                        <span class="text-muted">↳</span> {{ $room->name }}
                                    </td>
                                    <td>
                                        @if ($room->latitude)
                                            {{ $room->latitude }}
                                        @else
                                            <span class="text-muted">inherit</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($room->longitude)
                                            {{ $room->longitude }}
                                        @else
                                            <span class="text-muted">inherit</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($room->radius_meters)
                                            {{ $room->radius_meters }}
                                        @else
                                            <span class="text-muted">inherit</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.settings.venues.edit', $room) }}"
                                            class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                            <span class="ri ri-pencil-line"></span>
                                        </a>
                                        <form action="{{ route('admin.settings.venues.destroy', $room) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Delete room {{ $room->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <span class="ri ri-delete-bin-line"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr id="emptyRow">
                                <td colspan="5" class="text-center text-muted py-4">No venues configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- No results message --}}
            <div id="noResults" class="text-center text-muted py-4" style="display:none;">
                <span class="ri ri-search-line ri-2x d-block mb-2"></span>
                No venues match your filters.
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('venueSearch');
            const buildingFilter = document.getElementById('buildingFilter');
            const coordsFilter = document.getElementById('coordsFilter');
            const rows = document.querySelectorAll('.venue-row');
            const noResults = document.getElementById('noResults');
            const filterCountEl = document.getElementById('filterCount');
            const visibleCountEl = document.getElementById('visibleCount');
            const totalCount = rows.length;

            // Track collapsed buildings
            const collapsed = new Set();

            // Toggle rooms visibility
            document.querySelectorAll('.toggle-rooms').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.building;
                    const icon = this.querySelector('.ri');
                    if (!icon) return;
                    if (collapsed.has(id)) {
                        collapsed.delete(id);
                        icon.classList.replace('ri-arrow-right-s-line', 'ri-arrow-down-s-line');
                    } else {
                        collapsed.add(id);
                        icon.classList.replace('ri-arrow-down-s-line', 'ri-arrow-right-s-line');
                    }
                    applyFilters();
                });
            });

            function applyFilters() {
                const query = searchInput.value.toLowerCase().trim();
                const selectedBuilding = buildingFilter.value;
                const coordsVal = coordsFilter.value;
                let visibleBuildings = new Set();
                let visible = 0;
                const isFiltering = query || selectedBuilding || coordsVal;

                rows.forEach(row => {
                    const type = row.dataset.type;
                    const buildingId = row.dataset.buildingId;
                    const name = row.dataset.name;
                    const hasCoords = row.dataset.hasCoords === '1';
                    let show = true;

                    // Building dropdown filter
                    if (selectedBuilding && buildingId !== selectedBuilding) {
                        show = false;
                    }

                    // Search filter
                    if (query && !name.includes(query)) {
                        show = false;
                    }

                    // Coordinates filter
                    if (coordsVal === 'has' && !hasCoords) {
                        show = false;
                    }
                    if (coordsVal === 'inherit' && hasCoords) {
                        show = false;
                    }

                    // Collapsed building hides rooms
                    if (type === 'room' && collapsed.has(buildingId) && !isFiltering) {
                        show = false;
                    }

                    // When searching, auto-show the parent building if a room matches
                    if (type === 'room' && show) {
                        visibleBuildings.add(buildingId);
                    }

                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // If searching and a room is visible, ensure its building row is also visible
                if (isFiltering) {
                    rows.forEach(row => {
                        if (row.dataset.type === 'building' && visibleBuildings.has(row.dataset
                                .buildingId)) {
                            row.style.display = '';
                        }
                    });
                    // Recount
                    visible = 0;
                    rows.forEach(row => {
                        if (row.style.display !== 'none') visible++;
                    });
                }

                noResults.style.display = (visible === 0) ? '' : 'none';

                // Show filter count only when actively filtering
                if (isFiltering) {
                    filterCountEl.style.display = '';
                    visibleCountEl.textContent = visible;
                } else {
                    filterCountEl.style.display = 'none';
                }
            }

            searchInput.addEventListener('input', applyFilters);
            buildingFilter.addEventListener('change', applyFilters);
            coordsFilter.addEventListener('change', applyFilters);

            // Debounced search for performance
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 150);
            });
        });
    </script>
@endsection
