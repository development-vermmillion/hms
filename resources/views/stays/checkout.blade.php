@extends('layouts.app')
@section('title', 'Check-out')
@section('heading', 'Check-out · Room '.$stay->room->room_number)

@section('content')
    <form class="stack" method="POST" action="{{ route('stays.checkout', $stay) }}">
        @csrf

        <section class="glass pad">
            <h2>1 · Verify items returned</h2>
            <p class="muted" style="margin-top:0">Count what the guest is handing back. Anything short is charged at the item's replacement price.</p>
            <div class="table-wrap"><table>
                <thead><tr><th>Item</th><th>Issued</th><th style="width:160px">Returned</th><th class="num">Replacement price</th></tr></thead>
                <tbody>
                @forelse($stay->amenities as $a)
                    <tr>
                        <td><b>{{ $a->item->name }}</b></td>
                        <td>{{ $a->qty_issued }}</td>
                        <td><input type="number" min="0" max="{{ $a->qty_issued }}" name="returns[{{ $a->id }}]" value="{{ old('returns.'.$a->id, $a->qty_issued) }}"></td>
                        <td class="num">{{ money($a->item->replacement_price) }} each</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No items were issued to this room.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="glass pad">
            <h2>2 · Bill so far</h2>
            <div class="totals" style="margin-left:0">
                <div><span>Room ({{ $preview['nights'] }} night(s))</span><span>{{ money($preview['room']) }}</span></div>
                <div><span>Room service</span><span>{{ money($preview['food']) }}</span></div>
                <div><span>Extra charges</span><span>{{ money($preview['extra']) }}</span></div>
                <div><span>Already paid</span><span>{{ money($preview['paid']) }}</span></div>
            </div>
            <p class="muted small">Tax, missing-item charges and the final balance are calculated on the invoice after you confirm.</p>
        </section>

        <section class="glass pad">
            <div class="grid g-3" style="align-items:end">
                <x-staff-code uid="co"/>
                <div class="row" style="grid-column: span 2">
                    <button class="btn danger" type="submit">Confirm check-out</button>
                    <a class="btn" href="{{ route('stays.show', $stay) }}">Back</a>
                </div>
            </div>
            <p class="muted small" style="margin-bottom:0">The room moves to cleaning and a housekeeping task is created automatically.</p>
        </section>
    </form>
@endsection
