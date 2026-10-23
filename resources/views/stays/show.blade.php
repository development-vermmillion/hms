@extends('layouts.app')
@section('title', 'Stay #'.$stay->id)
@section('heading', 'Stay #'.$stay->id.' · '.$stay->guest->name)

@section('actions')
    @if($stay->invoice)<a class="btn" href="{{ route('invoices.show', $stay->invoice) }}">View invoice</a>@endif
    @if($stay->status === 'checked_in')<a class="btn danger" href="{{ route('stays.checkout', $stay) }}">Check out</a>@endif
@endsection

@section('content')
    @php($inHouse = $stay->status === 'checked_in')

    <div class="grid g-main">
        <section class="glass pad">
            <div class="row between"><h2>Stay details</h2><x-pill :status="$stay->status"/></div>
            <dl class="kv">
                <dt>Guest</dt><dd><a href="{{ route('guests.show', $stay->guest) }}">{{ $stay->guest->name }}</a> · {{ $stay->guest->phone }}</dd>
                <dt>ID proof</dt><dd>{{ $stay->guest->id_proof_type ? $stay->guest->id_proof_type.' · '.$stay->guest->id_proof_number : 'Not recorded' }}</dd>
                <dt>Room</dt><dd><b>{{ $stay->room->room_number }}</b> · {{ $stay->room->type->name }} <x-pill :status="$stay->room->status"/></dd>
                <dt>Guests</dt><dd>{{ $stay->adults }} adult(s), {{ $stay->children }} child(ren)</dd>
                <dt>Arrival</dt><dd>{{ $stay->arrival_date->format('d M Y') }}@if($stay->check_in_at) · checked in {{ $stay->check_in_at->format('d M, H:i') }}@endif</dd>
                <dt>Departure</dt><dd>{{ $stay->expected_check_out->format('d M Y') }}@if($stay->check_out_at) · checked out {{ $stay->check_out_at->format('d M, H:i') }}@endif</dd>
                <dt>Rate</dt><dd>{{ money($stay->rate) }} / night · {{ $stay->nights() }} night(s) = {{ money($stay->roomCharges()) }}</dd>
                <dt>Booked by</dt><dd>{{ $stay->creator?->name }} <span class="muted">({{ $stay->creator?->employee_code }})</span></dd>
                @if($stay->checkedInBy)<dt>Checked in by</dt><dd>{{ $stay->checkedInBy->name }} <span class="muted">({{ $stay->checkedInBy->employee_code }})</span></dd>@endif
                @if($stay->checkedOutBy)<dt>Checked out by</dt><dd>{{ $stay->checkedOutBy->name }} <span class="muted">({{ $stay->checkedOutBy->employee_code }})</span></dd>@endif
                @if($stay->status === 'cancelled')<dt>Cancelled</dt><dd>{{ $stay->cancel_reason }} · {{ $stay->cancelledBy?->name }}</dd>@endif
                @if($stay->notes)<dt>Notes</dt><dd>{{ $stay->notes }}</dd>@endif
            </dl>
        </section>

        <section class="glass pad stack">
            <h2>Money</h2>
            <dl class="kv" style="grid-template-columns:120px 1fr">
                <dt>Room</dt><dd>{{ money($stay->roomCharges()) }}</dd>
                <dt>Room service</dt><dd>{{ money($stay->foodTotal()) }}</dd>
                <dt>Extras</dt><dd>{{ money($stay->extrasTotal()) }}</dd>
                <dt>Paid so far</dt><dd>{{ money($stay->paidTotal()) }}</dd>
                @if($stay->invoice)<dt>Invoice</dt><dd><a href="{{ route('invoices.show', $stay->invoice) }}">{{ $stay->invoice->number }}</a> <x-pill :status="$stay->invoice->status"/></dd>@endif
            </dl>

            @if($stay->status === 'reserved')
                <form method="POST" action="{{ route('stays.check-in', $stay) }}" class="stack">@csrf
                    <x-staff-code uid="ci"/><button class="btn good block" type="submit">Check in guest</button>
                </form>
                <details class="fold"><summary class="btn danger sm" style="list-style:none">Cancel reservation</summary>
                    <form method="POST" action="{{ route('stays.cancel', $stay) }}" class="stack">@csrf
                        <input name="cancel_reason" placeholder="Reason for cancellation" required>
                        <x-staff-code uid="cx"/><button class="btn danger" type="submit">Confirm cancellation</button>
                    </form>
                </details>
            @endif

            @if($stay->status !== 'cancelled')
                <details class="fold"><summary class="btn sm" style="list-style:none">Record payment</summary>
                    <form method="POST" action="{{ route('stays.payments.store', $stay) }}" class="stack">@csrf
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required>
                        <select name="kind"><option value="payment">Payment</option><option value="advance">Advance</option></select>
                        <input name="note" placeholder="Note (optional)">
                        <x-staff-code uid="pay"/><button class="btn primary" type="submit">Save payment</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('stays.invoice', $stay) }}" class="stack">@csrf
                    <details class="fold"><summary class="btn sm" style="list-style:none">{{ $stay->invoice ? 'Refresh invoice' : 'Generate invoice' }}</summary>
                        <div class="stack"><x-staff-code uid="inv"/><button class="btn primary" type="submit">Generate</button></div>
                    </details>
                </form>
            @endif
        </section>
    </div>

    {{-- Room service --}}
    <div class="grid g-2">
        <section class="glass pad">
            <h2>Room service</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Order</th><th>Items</th><th class="num">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($stay->orders as $order)
                    <tr>
                        <td>#{{ $order->id }}<div class="muted small">{{ $order->created_at->format('d M H:i') }} · {{ $order->creator?->employee_code }}</div></td>
                        <td>@foreach($order->items as $i){{ $i->qty }}× {{ $i->name }}@if(!$loop->last), @endif @endforeach</td>
                        <td class="num">{{ money($order->total) }}</td>
                        <td><x-pill :status="$order->status"/></td>
                        <td>
                            @if($order->status === 'placed')
                                <details class="fold"><summary class="btn sm" style="list-style:none">Update</summary>
                                    <form method="POST" action="{{ route('orders.status', $order) }}" class="stack" style="min-width:200px">@csrf @method('PATCH')
                                        <select name="status"><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select>
                                        <x-staff-code :uid="'o'.$order->id"/><button class="btn sm primary" type="submit">Save</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No orders yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>

            @if($inHouse)
                <details class="fold" style="margin-top:14px"><summary class="btn primary sm" style="list-style:none">+ New food order</summary>
                    <form method="POST" action="{{ route('stays.orders.store', $stay) }}" class="stack">@csrf
                        @forelse($foodItems as $food)
                            <div class="row between"><span>{{ $food->name }} <span class="muted small">{{ money($food->price) }}</span></span>
                                <input style="width:80px" type="number" min="0" max="50" name="items[{{ $food->id }}]" value="0"></div>
                        @empty
                            <p class="muted">No menu items. The owner can add them under Room service menu.</p>
                        @endforelse
                        <input name="notes" placeholder="Notes (optional)">
                        <x-staff-code uid="food"/><button class="btn primary" type="submit">Place order</button>
                    </form>
                </details>
            @endif
        </section>

        <section class="glass pad">
            <h2>Room items (towels, kettle, slippers, plates…)</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Item</th><th>Issued</th><th>Returned</th><th class="num">Penalty</th></tr></thead>
                <tbody>
                @forelse($stay->amenities as $a)
                    <tr>
                        <td>{{ $a->item->name }}<div class="muted small">by {{ $a->issuer?->employee_code }}</div></td>
                        <td>{{ $a->qty_issued }}</td>
                        <td>@if($a->qty_returned === null)<span class="muted">pending</span>
                            @elseif($a->missing() > 0)<span style="color:var(--bad)">{{ $a->qty_returned }} ({{ $a->missing() }} missing)</span>
                            @else {{ $a->qty_returned }} ✓ @endif</td>
                        <td class="num">{{ $a->penalty_amount > 0 ? money($a->penalty_amount) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Nothing issued.</td></tr>
                @endforelse
                </tbody>
            </table></div>

            @if($inHouse)
                <details class="fold" style="margin-top:14px"><summary class="btn primary sm" style="list-style:none">+ Issue an item</summary>
                    <form method="POST" action="{{ route('stays.amenities.store', $stay) }}" class="stack">@csrf
                        <select name="amenity_item_id" required>@foreach($amenityItems as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
                        <input type="number" min="1" max="50" name="qty_issued" value="1" required>
                        <x-staff-code uid="am"/><button class="btn primary" type="submit">Issue</button>
                    </form>
                </details>
            @endif

            <h2 style="margin-top:26px">Extra charges</h2>
            <div class="table-wrap"><table>
                <tbody>
                @forelse($stay->charges as $c)
                    <tr><td>{{ $c->description }}<div class="muted small">{{ $c->created_at->format('d M H:i') }} · {{ $c->creator?->employee_code }}</div></td><td class="num">{{ money($c->amount) }}</td></tr>
                @empty
                    <tr><td class="empty">No extra charges.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($inHouse)
                <details class="fold" style="margin-top:14px"><summary class="btn primary sm" style="list-style:none">+ Add charge</summary>
                    <form method="POST" action="{{ route('stays.charges.store', $stay) }}" class="stack">@csrf
                        <input name="description" placeholder="e.g. Laundry, extra bed" required>
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required>
                        <x-staff-code uid="chg"/><button class="btn primary" type="submit">Add</button>
                    </form>
                </details>
            @endif
        </section>
    </div>

    <section class="glass pad">
        <h2>Payments</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>When</th><th>Type</th><th>Note</th><th>Received by</th><th class="num">Amount</th></tr></thead>
            <tbody>
            @forelse($stay->payments as $p)
                <tr><td>{{ $p->paid_at->format('d M Y H:i') }}</td><td>{{ ucfirst($p->kind) }}</td><td>{{ $p->note ?: '—' }}</td><td>{{ $p->receiver?->name }} <span class="muted">({{ $p->receiver?->employee_code }})</span></td><td class="num">{{ money($p->amount) }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty">No payments recorded.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
