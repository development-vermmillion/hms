@extends('layouts.app')
@section('title', 'Stays & bookings')
@section('heading', 'Stays & bookings')

@section('actions')
    <a class="btn primary" href="{{ route('stays.create') }}">+ New booking / check-in</a>
@endsection

@section('content')
    <form class="glass tight row" method="GET" action="{{ route('stays.index') }}">
        <select name="status" style="width:auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(['reserved', 'checked_in', 'checked_out', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <input style="max-width:320px" type="search" name="q" value="{{ $search }}" placeholder="Guest, phone or room">
        <button class="btn sm primary" type="submit">Search</button>
    </form>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>Guest</th><th>Room</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Bill</th><th></th></tr></thead>
            <tbody>
            @forelse($stays as $stay)
                <tr>
                    <td>{{ $stay->id }}</td>
                    <td><a href="{{ route('stays.show', $stay) }}">{{ $stay->guest->name }}</a><div class="muted small">{{ $stay->guest->phone }}</div></td>
                    <td>{{ $stay->room->room_number }} <span class="muted small">{{ $stay->room->type->name }}</span></td>
                    <td>{{ ($stay->check_in_at ?? $stay->arrival_date)->format('d M, H:i') }}</td>
                    <td>{{ ($stay->check_out_at ?? $stay->expected_check_out)->format('d M') }}</td>
                    <td><x-pill :status="$stay->status"/></td>
                    <td>@if($stay->invoice)<x-pill :status="$stay->invoice->status"/>@else <span class="muted">—</span>@endif</td>
                    <td class="num"><a class="btn sm" href="{{ route('stays.show', $stay) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No stays found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $stays->links() }}</div>
    </section>
@endsection
