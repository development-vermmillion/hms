@extends('layouts.app')
@section('title', 'Maintenance')
@section('heading', 'Maintenance requests')

@section('content')
    <div class="grid g-main">
        <section class="glass pad">
            <div class="row between"><h2>Requests</h2>
                <div class="row">
                    <a class="btn sm {{ $status === 'active' ? 'primary' : '' }}" href="{{ route('maintenance.index') }}">Active</a>
                    <a class="btn sm {{ $status === 'resolved' ? 'primary' : '' }}" href="{{ route('maintenance.index', ['status' => 'resolved']) }}">Resolved</a>
                    <a class="btn sm {{ $status === 'all' ? 'primary' : '' }}" href="{{ route('maintenance.index', ['status' => 'all']) }}">All</a>
                </div>
            </div>
            <div class="table-wrap"><table>
                <thead><tr><th>Issue</th><th>Room</th><th>Priority</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td><b>{{ $req->title }}</b><div class="muted small">{{ $req->description }}</div>
                            <div class="muted small">Reported {{ $req->created_at->format('d M H:i') }} by {{ $req->reporter?->name }} ({{ $req->reporter?->employee_code }})</div></td>
                        <td>{{ $req->room?->room_number ?? 'General' }}</td>
                        <td><x-pill :status="$req->priority"/></td>
                        <td><x-pill :status="$req->status"/></td>
                        <td>
                            @if($req->status !== 'resolved')
                                <details class="fold"><summary class="btn sm" style="list-style:none">Update</summary>
                                    <form method="POST" action="{{ route('maintenance.update', $req) }}" class="stack" style="min-width:210px">@csrf @method('PATCH')
                                        <select name="status"><option value="in_progress">In progress</option><option value="resolved">Resolved</option></select>
                                        <x-staff-code :uid="'m'.$req->id"/>
                                        <button class="btn sm primary" type="submit">Save</button>
                                    </form>
                                </details>
                            @else
                                <span class="muted small">{{ $req->resolver?->employee_code }} · {{ $req->resolved_at?->format('d M') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No requests here.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="pagination">{{ $requests->links() }}</div>
        </section>

        <section class="glass pad">
            <h2>Log a request</h2>
            <form method="POST" action="{{ route('maintenance.store') }}" class="stack">@csrf
                <div><label>Room (optional)</label>
                    <select name="room_id"><option value="">General / common area</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_number }}</option>@endforeach</select>
                </div>
                <div><label>Issue</label><input name="title" value="{{ old('title') }}" required></div>
                <div><label>Details</label><textarea name="description" rows="3">{{ old('description') }}</textarea></div>
                <div><label>Priority</label>
                    <select name="priority">@foreach(['low', 'normal', 'high', 'urgent'] as $p)<option value="{{ $p }}" @selected($p === 'normal')>{{ ucfirst($p) }}</option>@endforeach</select>
                </div>
                <div class="check"><input id="blocks_room" type="checkbox" name="blocks_room" value="1"><label for="blocks_room">Take this room out of service</label></div>
                <x-staff-code uid="mt"/>
                <button class="btn primary" type="submit">Log request</button>
            </form>
        </section>
    </div>
@endsection
