@extends('layouts.app')
@section('title', 'Rooms')
@section('heading', 'Rooms')

@section('content')
    @php($owner = auth()->user()->isOwner())

    <div class="glass tight row">
        <a class="btn sm {{ request('status') ? '' : 'primary' }}" href="{{ route('rooms.index') }}">All</a>
        @foreach(\App\Models\Room::STATUSES as $s)
            <a class="btn sm {{ request('status') === $s ? 'primary' : '' }}" href="{{ route('rooms.index', ['status' => $s]) }}">{{ ucfirst($s) }}</a>
        @endforeach
        <form class="right row" method="GET" action="{{ route('rooms.index') }}">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <select name="type" onchange="this.form.submit()" style="width:auto">
                <option value="">All types</option>
                @foreach($types as $t)<option value="{{ $t->id }}" @selected(request('type') == $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </form>
    </div>

    @if($owner)
        <section class="glass pad">
            <h2>{{ $editing ? 'Edit room '.$editing->room_number : 'Add a room' }}</h2>
            <form method="POST" action="{{ $editing ? route('rooms.update', $editing) : route('rooms.store') }}">
                @csrf @if($editing) @method('PUT') @endif
                <div class="form-grid">
                    <div><label>Room number</label><input name="room_number" value="{{ old('room_number', $editing?->room_number) }}" required></div>
                    <div><label>Type</label>
                        <select name="room_type_id" required>
                            @foreach($types as $t)<option value="{{ $t->id }}" @selected(old('room_type_id', $editing?->room_type_id) == $t->id)>{{ $t->name }} ({{ money($t->base_price) }})</option>@endforeach
                        </select>
                    </div>
                    <div><label>Floor</label><input name="floor" value="{{ old('floor', $editing?->floor) }}"></div>
                    <div><label>Capacity (guests)</label><input type="number" min="1" name="capacity" value="{{ old('capacity', $editing?->capacity ?? 2) }}" required></div>
                    <div><label>Price per night</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $editing?->price) }}" required></div>
                    <div><label>Notes</label><input name="notes" value="{{ old('notes', $editing?->notes) }}"></div>
                </div>
                <div class="row" style="margin-top:16px">
                    <button class="btn primary" type="submit">{{ $editing ? 'Save changes' : 'Add room' }}</button>
                    @if($editing)<a class="btn" href="{{ route('rooms.index') }}">Cancel</a>@endif
                    @if($types->isEmpty())<span class="muted small">Create a room type first.</span>@endif
                </div>
            </form>
        </section>
    @endif

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Room</th><th>Type</th><th>Floor</th><th>Capacity</th><th class="num">Price / night</th><th>Status</th><th>In room</th><th></th></tr></thead>
            <tbody>
            @forelse($rooms as $room)
                <tr>
                    <td><b>{{ $room->room_number }}</b></td>
                    <td>{{ $room->type->name }}</td>
                    <td>{{ $room->floor ?: '—' }}</td>
                    <td>{{ $room->capacity }}</td>
                    <td class="num">{{ money($room->price) }}</td>
                    <td><x-pill :status="$room->status"/></td>
                    <td>
                        @if($room->currentStay)<a href="{{ route('stays.show', $room->currentStay) }}">{{ $room->currentStay->guest->name }}</a>
                        @else <span class="muted">—</span> @endif
                    </td>
                    <td>
                        <div class="row" style="justify-content:flex-end">
                            @if($room->status === 'available')
                                <a class="btn sm good" href="{{ route('stays.create', ['room' => $room->id]) }}">Book</a>
                            @endif
                            @if(in_array($room->status, ['available', 'cleaning', 'maintenance']))
                                <details class="fold">
                                    <summary class="btn sm" style="list-style:none;margin:0">Status</summary>
                                    <form method="POST" action="{{ route('rooms.status', $room) }}" class="stack" style="margin-top:8px;min-width:210px">
                                        @csrf @method('PATCH')
                                        <select name="status">
                                            @foreach(['available', 'cleaning', 'maintenance'] as $s)<option value="{{ $s }}" @selected($room->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                                        </select>
                                        <x-staff-code :uid="'r'.$room->id"/>
                                        <button class="btn sm primary" type="submit">Update</button>
                                    </form>
                                </details>
                            @endif
                            @if($owner)
                                <a class="btn sm" href="{{ route('rooms.index', ['edit' => $room->id]) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Delete room {{ $room->room_number }}?')">
                                    @csrf @method('DELETE')<button class="btn sm danger" type="submit">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No rooms found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
