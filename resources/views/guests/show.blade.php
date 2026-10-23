@extends('layouts.app')
@section('title', $guest->name)
@section('heading', $guest->name)

@section('actions')
    <a class="btn" href="{{ route('guests.edit', $guest) }}">Edit</a>
    <a class="btn primary" href="{{ route('stays.create', ['guest' => $guest->id]) }}">+ New stay</a>
@endsection

@section('content')
    <div class="grid g-main">
        <section class="glass pad">
            <h2>Stay history</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Room</th><th>Arrival</th><th>Departure</th><th>Status</th><th class="num">Billed</th><th></th></tr></thead>
                <tbody>
                @forelse($guest->stays as $stay)
                    <tr>
                        <td><a href="{{ route('stays.show', $stay) }}">{{ $stay->room->room_number }}</a> <span class="muted small">{{ $stay->room->type->name }}</span></td>
                        <td>{{ ($stay->check_in_at ?? $stay->arrival_date)->format('d M Y') }}</td>
                        <td>{{ ($stay->check_out_at ?? $stay->expected_check_out)->format('d M Y') }}</td>
                        <td><x-pill :status="$stay->status"/></td>
                        <td class="num">{{ $stay->invoice ? money($stay->invoice->total) : '—' }}</td>
                        <td class="num">@if($stay->invoice)<a href="{{ route('invoices.show', $stay->invoice) }}">{{ $stay->invoice->number }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No stays yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="glass pad">
            <h2>Details</h2>
            <dl class="kv">
                <dt>Phone</dt><dd>{{ $guest->phone }}</dd>
                <dt>E-mail</dt><dd>{{ $guest->email ?: '—' }}</dd>
                <dt>ID proof</dt><dd>{{ $guest->id_proof_type ? $guest->id_proof_type.' · '.$guest->id_proof_number : '—' }}</dd>
                <dt>Address</dt><dd>{{ $guest->address ?: '—' }}</dd>
                <dt>Total stays</dt><dd>{{ $guest->stays->where('status', 'checked_out')->count() }} completed</dd>
            </dl>
        </section>
    </div>
@endsection
