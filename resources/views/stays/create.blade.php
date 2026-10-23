@extends('layouts.app')
@section('title', 'New booking')
@section('heading', 'New booking / check-in')

@section('content')
    <form class="stack" method="POST" action="{{ route('stays.store') }}">
        @csrf

        <section class="glass pad">
            <h2>Guest</h2>
            <div class="form-grid">
                <div>
                    <label>Existing guest (optional)</label>
                    <select name="guest_id" id="guest_id">
                        <option value="">— New guest —</option>
                        @foreach($guests as $g)<option value="{{ $g->id }}" @selected(old('guest_id', $selectedGuest) == $g->id)>{{ $g->name }} · {{ $g->phone }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="form-grid" id="new-guest" style="margin-top:14px">
                <div><label>Full name</label><input name="guest_name" value="{{ old('guest_name') }}"></div>
                <div><label>Phone</label><input name="guest_phone" value="{{ old('guest_phone') }}"></div>
                <div><label>E-mail</label><input type="email" name="guest_email" value="{{ old('guest_email') }}"></div>
                <div><label>ID proof type</label>
                    <select name="id_proof_type">
                        <option value="">—</option>
                        @foreach(['Aadhaar', 'Passport', 'Driving licence', 'Voter ID', 'PAN', 'Other'] as $t)<option @selected(old('id_proof_type') === $t)>{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div><label>ID proof number</label><input name="id_proof_number" value="{{ old('id_proof_number') }}"></div>
                <div><label>Address</label><input name="guest_address" value="{{ old('guest_address') }}"></div>
            </div>
            @error('id_proof_number')<div class="error">{{ $message }}</div>@enderror
        </section>

        <section class="glass pad">
            <h2>Stay</h2>
            <div class="form-grid">
                <div>
                    <label>Room (available only)</label>
                    <select name="room_id" id="room_id" required>
                        <option value="">Select a room</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" data-price="{{ $r->price }}" @selected(old('room_id', $selectedRoom) == $r->id)>{{ $r->room_number }} · {{ $r->type->name }} · {{ money($r->price) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Arrival date</label><input type="date" name="arrival_date" value="{{ old('arrival_date', today()->toDateString()) }}" required></div>
                <div><label>Expected check-out</label><input type="date" name="expected_check_out" value="{{ old('expected_check_out', today()->addDay()->toDateString()) }}" required></div>
                <div><label>Adults</label><input type="number" min="1" name="adults" value="{{ old('adults', 1) }}" required></div>
                <div><label>Children</label><input type="number" min="0" name="children" value="{{ old('children', 0) }}"></div>
                <div><label>Rate per night</label><input type="number" step="0.01" min="0" name="rate" id="rate" value="{{ old('rate') }}" required></div>
                <div><label>Advance received</label><input type="number" step="0.01" min="0" name="advance" value="{{ old('advance') }}"></div>
            </div>
            <div class="field" style="margin-top:14px"><label>Notes</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
        </section>

        <section class="glass pad">
            <div class="grid g-3" style="align-items:end">
                <x-staff-code uid="stay"/>
                <div class="row" style="grid-column: span 2">
                    <button class="btn good" type="submit" name="action" value="check_in">Check in now</button>
                    <button class="btn primary" type="submit" name="action" value="reserve">Reserve room</button>
                    <a class="btn" href="{{ route('stays.index') }}">Cancel</a>
                </div>
            </div>
            <p class="muted small" style="margin-bottom:0">Checking in needs the guest's ID proof. Reserving holds the room until the guest arrives.</p>
        </section>
    </form>
@endsection

@push('scripts')
<script>
    // Fill the nightly rate from the chosen room; hide new-guest fields when an existing guest is picked.
    const room = document.getElementById('room_id'), rate = document.getElementById('rate');
    const guest = document.getElementById('guest_id'), box = document.getElementById('new-guest');
    function syncRate() { const o = room.selectedOptions[0]; if (o && o.dataset.price && !rate.dataset.touched) rate.value = o.dataset.price; }
    function syncGuest() { box.style.display = guest.value ? 'none' : ''; }
    rate.addEventListener('input', () => rate.dataset.touched = '1');
    room.addEventListener('change', () => { delete rate.dataset.touched; syncRate(); });
    guest.addEventListener('change', syncGuest);
    if (!rate.value) syncRate(); syncGuest();
</script>
@endpush
