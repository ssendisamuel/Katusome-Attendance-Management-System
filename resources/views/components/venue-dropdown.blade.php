{{-- Venue dropdown partial — include in series & schedule forms --}}
@php
    $venues = \App\Models\Venue::whereNull('parent_id')->with('children')->orderBy('name')->get();
    $selectedVenueId = old('venue_id', $selectedVenue ?? null);
@endphp

<label class="form-label">Venue <small class="text-muted">(physical location)</small></label>
<select name="venue_id" class="form-select select2" id="venue-select" data-placeholder="Search venue..."
    data-allow-clear="true">
    <option value="">— Select Venue —</option>
    @foreach ($venues as $building)
        @if ($building->children->count())
            <optgroup label="{{ $building->name }}">
                @foreach ($building->children as $room)
                    <option value="{{ $room->id }}" {{ $selectedVenueId == $room->id ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </optgroup>
        @else
            <option value="{{ $building->id }}" {{ $selectedVenueId == $building->id ? 'selected' : '' }}>
                {{ $building->name }}
            </option>
        @endif
    @endforeach
</select>
@error('venue_id')
    <div class="text-danger small">{{ $message }}</div>
@enderror
