@extends('layouts.app')
@section('title', 'Billing')
@section('heading', 'Billing & payments')

@section('content')
    <div class="glass stat warn" style="max-width:360px"><div class="label">Total outstanding</div><div class="value">{{ money($outstanding) }}</div></div>

    <form class="glass tight row" method="GET" action="{{ route('invoices.index') }}">
        <select name="status" style="width:auto" onchange="this.form.submit()">
            <option value="">All payment statuses</option>
            @foreach(['unpaid', 'partial', 'paid'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <input style="max-width:320px" type="search" name="q" value="{{ $search }}" placeholder="Invoice number or guest">
        <button class="btn sm primary" type="submit">Search</button>
    </form>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Invoice</th><th>Date</th><th>Guest</th><th>Room</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($invoices as $inv)
                <tr>
                    <td><a href="{{ route('invoices.show', $inv) }}">{{ $inv->number }}</a></td>
                    <td>{{ $inv->created_at->format('d M Y') }}</td>
                    <td>{{ $inv->stay->guest->name }}</td>
                    <td>{{ $inv->stay->room->room_number }}</td>
                    <td class="num">{{ money($inv->total) }}</td>
                    <td class="num">{{ money($inv->paid) }}</td>
                    <td class="num">{{ money($inv->balance) }}</td>
                    <td><x-pill :status="$inv->status"/></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No invoices yet. They are created at check-out, or from a stay page.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $invoices->links() }}</div>
    </section>
@endsection
