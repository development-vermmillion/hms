@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('actions')
    <a class="btn primary" href="{{ route('stays.create') }}">+ New booking / check-in</a>
@endsection

@section('content')
    <div class="grid g-4">
        <div class="glass stat"><div class="label"><x-icon name="rooms"/> Total rooms</div><div class="value">{{ $stats['total_rooms'] }}</div><div class="hint">{{ $stats['reserved'] }} reserved · {{ $stats['cleaning'] }} cleaning · {{ $stats['maintenance'] }} maintenance</div></div>
        <div class="glass stat ok"><div class="label">Available rooms</div><div class="value">{{ $stats['available'] }}</div><div class="hint">Ready to sell now</div></div>
        <div class="glass stat accent"><div class="label">Occupied rooms</div><div class="value">{{ $stats['occupied'] }}</div><div class="hint">{{ $stats['total_rooms'] ? round($stats['occupied'] / $stats['total_rooms'] * 100) : 0 }}% occupancy</div></div>
        <div class="glass stat warn"><div class="label">Pending payments</div><div class="value">{{ money($stats['pending_amount']) }}</div><div class="hint">{{ $stats['pending_count'] }} open {{ Str::plural('invoice', $stats['pending_count']) }}</div></div>
    </div>

    <div class="grid g-4">
        <div class="glass stat"><div class="label">Today's check-ins</div><div class="value">{{ $stats['checkins_today'] }}</div><div class="hint">{{ $arrivals->count() }} arrival(s) waiting</div></div>
        <div class="glass stat"><div class="label">Today's check-outs</div><div class="value">{{ $stats['checkouts_today'] }}</div><div class="hint">{{ $departures->count() }} departure(s) due</div></div>
        <div class="glass stat"><div class="label">Today's bookings</div><div class="value">{{ $stats['bookings_today'] }}</div><div class="hint">Reservations and walk-ins</div></div>
        <div class="glass stat ok"><div class="label">Today's revenue</div><div class="value">{{ money($stats['revenue_today']) }}</div><div class="hint">Payments received today</div></div>
    </div>

    <div class="grid g-main">
        <section class="glass pad">
            <div class="row between"><h2>Room board</h2>
                <div class="legend">
                    @foreach(['available' => '#7ef0c2', 'occupied' => '#9bc4ff', 'reserved' => '#ffd86b', 'cleaning' => '#c1a8ff', 'maintenance' => '#ff9fa8'] as $s => $c)
                        <span><i style="background:{{ $c }}"></i>{{ ucfirst($s) }}</span>
                    @endforeach
                </div>
            </div>
            <div class="room-board" style="margin-top:14px">
                @forelse($rooms as $room)
                    <a class="room-tile {{ $room->status }}" href="{{ route('rooms.index', ['status' => $room->status]) }}">
                        <b>{{ $room->room_number }}</b><small>{{ $room->type->name }}</small>
                        <x-pill :status="$room->status"/>
                    </a>
                @empty
                    <div class="empty">No rooms yet. The owner can add rooms under Rooms.</div>
                @endforelse
            </div>
        </section>

        <section class="glass pad">
            <h2>Revenue · last 7 days</h2>
            @php($max = max(1, $chart->max('value')))
            <div class="bars">
                @foreach($chart as $bar)
                    <div class="bar" title="{{ money($bar['value']) }}">
                        <em>{{ $bar['value'] > 0 ? number_format($bar['value'], 0) : '' }}</em>
                        <i style="height: {{ max(3, $bar['value'] / $max * 100) }}%"></i>
                        <span>{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="row" style="margin-top:18px">
                <a class="btn sm" href="{{ route('housekeeping.index') }}">{{ $openHousekeeping }} cleaning task(s)</a>
                <a class="btn sm" href="{{ route('maintenance.index') }}">{{ $openMaintenance }} maintenance open</a>
            </div>
        </section>
    </div>

    <div class="grid g-2">
        <section class="glass pad">
            <h2>Arrivals due</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Guest</th><th>Room</th><th>Arrival</th><th></th></tr></thead>
                <tbody>
                @forelse($arrivals as $stay)
                    <tr>
                        <td><a href="{{ route('stays.show', $stay) }}">{{ $stay->guest->name }}</a></td>
                        <td>{{ $stay->room->room_number }}</td>
                        <td>{{ $stay->arrival_date->format('d M') }}</td>
                        <td class="num"><a class="btn sm" href="{{ route('stays.show', $stay) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No arrivals waiting.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="glass pad">
            <h2>Departures due</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Guest</th><th>Room</th><th>Due</th><th></th></tr></thead>
                <tbody>
                @forelse($departures as $stay)
                    <tr>
                        <td><a href="{{ route('stays.show', $stay) }}">{{ $stay->guest->name }}</a></td>
                        <td>{{ $stay->room->room_number }}</td>
                        <td>{{ $stay->expected_check_out->format('d M') }}</td>
                        <td class="num"><a class="btn sm" href="{{ route('stays.checkout', $stay) }}">Check out</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No departures due.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
@endsection
