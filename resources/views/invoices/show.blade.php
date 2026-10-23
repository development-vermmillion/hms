@extends('layouts.app')
@section('title', $invoice->number)
@section('heading', 'Invoice '.$invoice->number)

@section('actions')
    <a class="btn" href="{{ route('stays.show', $invoice->stay) }}">Open stay</a>
    <button class="btn primary" type="button" onclick="window.print()">Print</button>
@endsection

@section('content')
    @php($stay = $invoice->stay)
    @php($hotel = $invoice->hotel)

    <section class="glass pad">
        <div class="row between" style="align-items:flex-start">
            <div>
                <h2 style="font-size:22px;margin-bottom:4px">{{ $hotel->name }}</h2>
                <div class="muted small">{{ $hotel->address }}{{ $hotel->city ? ', '.$hotel->city : '' }}</div>
                <div class="muted small">{{ $hotel->phone }} {{ $hotel->email ? '· '.$hotel->email : '' }}</div>
                @if($hotel->gst_number)<div class="muted small">GSTIN: {{ $hotel->gst_number }}</div>@endif
            </div>
            <div style="text-align:right">
                <div style="font-size:20px;font-weight:700">{{ $invoice->number }}</div>
                <div class="muted small">Issued {{ $invoice->created_at->format('d M Y') }}</div>
                <x-pill :status="$invoice->status"/>
            </div>
        </div>

        <hr style="border:0;border-top:1px solid rgba(255,255,255,.2);margin:20px 0">

        <div class="grid g-2">
            <dl class="kv" style="grid-template-columns:90px 1fr">
                <dt>Guest</dt><dd><b>{{ $stay->guest->name }}</b><br><span class="muted small">{{ $stay->guest->phone }}</span></dd>
                <dt>Address</dt><dd>{{ $stay->guest->address ?: '—' }}</dd>
            </dl>
            <dl class="kv" style="grid-template-columns:110px 1fr">
                <dt>Room</dt><dd>{{ $stay->room->room_number }} · {{ $stay->room->type->name }}</dd>
                <dt>Stay</dt><dd>{{ ($stay->check_in_at ?? $stay->arrival_date)->format('d M Y') }} → {{ ($stay->check_out_at ?? $stay->expected_check_out)->format('d M Y') }} ({{ $stay->nights() }} night(s))</dd>
                <dt>Served by</dt><dd>{{ $stay->checkedOutBy?->name ?? $invoice->creator?->name }} <span class="muted">({{ $stay->checkedOutBy?->employee_code ?? $invoice->creator?->employee_code }})</span></dd>
            </dl>
        </div>

        <div class="table-wrap" style="margin-top:18px"><table>
            <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Rate</th><th class="num">Amount</th></tr></thead>
            <tbody>
                <tr><td>Room {{ $stay->room->room_number }} ({{ $stay->room->type->name }})</td><td class="num">{{ $stay->nights() }}</td><td class="num">{{ money($stay->rate) }}</td><td class="num">{{ money($invoice->room_charges) }}</td></tr>
                @foreach($stay->orders->where('status', '!=', 'cancelled') as $order)
                    @foreach($order->items as $item)
                        <tr><td>Room service · {{ $item->name }}</td><td class="num">{{ $item->qty }}</td><td class="num">{{ money($item->price) }}</td><td class="num">{{ money($item->qty * $item->price) }}</td></tr>
                    @endforeach
                @endforeach
                @foreach($stay->charges as $charge)
                    <tr><td>{{ $charge->description }}</td><td class="num">1</td><td class="num">{{ money($charge->amount) }}</td><td class="num">{{ money($charge->amount) }}</td></tr>
                @endforeach
                @foreach($stay->amenities->where('penalty_amount', '>', 0) as $a)
                    <tr><td>Missing item · {{ $a->item->name }}</td><td class="num">{{ $a->missing() }}</td><td class="num">{{ money($a->item->replacement_price) }}</td><td class="num">{{ money($a->penalty_amount) }}</td></tr>
                @endforeach
            </tbody>
        </table></div>

        <div class="totals" style="margin-top:18px">
            <div><span>Room charges</span><span>{{ money($invoice->room_charges) }}</span></div>
            <div><span>Room service</span><span>{{ money($invoice->food_charges) }}</span></div>
            <div><span>Extra charges</span><span>{{ money($invoice->extra_charges) }}</span></div>
            <div><span>Missing items</span><span>{{ money($invoice->amenity_charges) }}</span></div>
            <div><span>Tax ({{ rtrim(rtrim(number_format($hotel->tax_percent, 2), '0'), '.') }}%)</span><span>{{ money($invoice->tax_amount) }}</span></div>
            @if($invoice->discount > 0)<div><span>Discount</span><span>− {{ money($invoice->discount) }}</span></div>@endif
            <div class="grand"><span>Total</span><span>{{ money($invoice->total) }}</span></div>
            <div><span>Paid (advance + payments)</span><span>{{ money($invoice->paid) }}</span></div>
            <div class="grand" style="color: {{ $invoice->balance > 0 ? 'var(--warn)' : 'var(--ok)' }}"><span>Balance due</span><span>{{ money($invoice->balance) }}</span></div>
        </div>

        @if($hotel->invoice_footer)<p class="muted" style="text-align:center;margin-top:26px">{{ $hotel->invoice_footer }}</p>@endif
    </section>

    <div class="grid g-2 no-print">
        <section class="glass pad">
            <h2>Payments received</h2>
            <div class="table-wrap"><table>
                <tbody>
                @forelse($stay->payments as $p)
                    <tr><td>{{ $p->paid_at->format('d M H:i') }}<div class="muted small">{{ ucfirst($p->kind) }} · {{ $p->receiver?->name }} ({{ $p->receiver?->employee_code }})</div></td><td class="num">{{ money($p->amount) }}</td></tr>
                @empty
                    <tr><td class="empty">Nothing received yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="glass pad stack">
            @if($invoice->balance > 0)
                <h2>Record payment</h2>
                <form method="POST" action="{{ route('stays.payments.store', $stay) }}" class="stack">@csrf
                    <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ $invoice->balance }}" required>
                    <input name="note" placeholder="Note (optional)">
                    <x-staff-code uid="invpay"/>
                    <button class="btn good" type="submit">Mark as received</button>
                </form>
            @else
                <h2>Fully settled</h2><p class="muted">No balance is pending on this invoice.</p>
            @endif

            @if(auth()->user()->isOwner())
                <details class="fold"><summary class="btn sm" style="list-style:none">Apply discount</summary>
                    <form method="POST" action="{{ route('invoices.discount', $invoice) }}" class="stack">@csrf @method('PATCH')
                        <input type="number" step="0.01" min="0" name="discount" value="{{ $invoice->discount }}" required>
                        <button class="btn primary" type="submit">Apply</button>
                    </form>
                </details>
            @endif
        </section>
    </div>
@endsection
