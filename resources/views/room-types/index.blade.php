@extends('layouts.app')
@section('title', 'Room types')
@section('heading', 'Room types')

@section('content')
    <section class="glass pad">
        <h2>{{ $editing ? 'Edit '.$editing->name : 'Add a room type' }}</h2>
        <form method="POST" action="{{ $editing ? route('room-types.update', $editing) : route('room-types.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <div class="form-grid">
                <div><label>Name (Single, Double, Deluxe, Suite…)</label><input name="name" value="{{ old('name', $editing?->name) }}" required></div>
                <div><label>Base price per night</label><input type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price', $editing?->base_price) }}" required></div>
                <div><label>Capacity</label><input type="number" min="1" name="capacity" value="{{ old('capacity', $editing?->capacity ?? 2) }}" required></div>
                <div><label>Description</label><input name="description" value="{{ old('description', $editing?->description) }}"></div>
            </div>
            <div class="row" style="margin-top:16px">
                <button class="btn primary" type="submit">{{ $editing ? 'Save changes' : 'Add type' }}</button>
                @if($editing)<a class="btn" href="{{ route('room-types.index') }}">Cancel</a>@endif
            </div>
        </form>
    </section>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Type</th><th>Capacity</th><th class="num">Base price</th><th>Rooms</th><th></th></tr></thead>
            <tbody>
            @forelse($types as $type)
                <tr>
                    <td><b>{{ $type->name }}</b><div class="muted small">{{ $type->description }}</div></td>
                    <td>{{ $type->capacity }}</td>
                    <td class="num">{{ money($type->base_price) }}</td>
                    <td>{{ $type->rooms_count }}</td>
                    <td><div class="row" style="justify-content:flex-end">
                        <a class="btn sm" href="{{ route('room-types.index', ['edit' => $type->id]) }}">Edit</a>
                        <form class="inline-form" method="POST" action="{{ route('room-types.destroy', $type) }}" onsubmit="return confirm('Delete {{ $type->name }}?')">
                            @csrf @method('DELETE')<button class="btn sm danger" type="submit">Delete</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No room types yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
