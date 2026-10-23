@extends('layouts.app')
@section('title', $cfg['title'])
@section('heading', $cfg['title'])

@section('content')
    @php($col = $cfg['price'])
    <section class="glass pad">
        <h2>{{ $editing ? 'Edit '.$editing->name : 'Add '.strtolower($cfg['noun']) }}</h2>
        <form method="POST" action="{{ $editing ? route('catalog.update', [$kind, $editing->id]) : route('catalog.store', $kind) }}">
            @csrf @if($editing) @method('PUT') @endif
            <div class="form-grid">
                <div><label>Name</label><input name="name" value="{{ old('name', $editing?->name) }}" required></div>
                <div><label>{{ $cfg['label'] }}</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $editing?->$col) }}" required></div>
                @if($editing)
                    <div class="check" style="align-self:end"><input id="is_active" type="checkbox" name="is_active" value="1" @checked($editing->is_active)><label for="is_active">Active</label></div>
                @endif
            </div>
            <div class="row" style="margin-top:16px">
                <button class="btn primary" type="submit">{{ $editing ? 'Save changes' : 'Add' }}</button>
                @if($editing)<a class="btn" href="{{ route('catalog.index', $kind) }}">Cancel</a>@endif
            </div>
        </form>
    </section>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Name</th><th class="num">{{ $cfg['label'] }}</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><b>{{ $item->name }}</b></td>
                    <td class="num">{{ money($item->$col) }}</td>
                    <td><x-pill :status="$item->is_active ? 'available' : 'cancelled'"/> <span class="muted small">{{ $item->is_active ? 'active' : 'hidden' }}</span></td>
                    <td><div class="row" style="justify-content:flex-end">
                        <a class="btn sm" href="{{ route('catalog.index', [$kind, 'edit' => $item->id]) }}">Edit</a>
                        @if($item->is_active)
                            <form class="inline-form" method="POST" action="{{ route('catalog.destroy', [$kind, $item->id]) }}">@csrf @method('DELETE')<button class="btn sm danger" type="submit">Hide</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
