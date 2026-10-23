@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Hotel settings')

@section('content')
    <form class="stack" method="POST" action="{{ route('settings.update') }}">
        @csrf @method('PUT')

        <section class="glass pad">
            <h2>Hotel details</h2>
            <div class="form-grid">
                <div><label>Hotel name</label><input name="name" value="{{ old('name', $hotel->name) }}" required></div>
                <div><label>Phone</label><input name="phone" value="{{ old('phone', $hotel->phone) }}"></div>
                <div><label>E-mail</label><input type="email" name="email" value="{{ old('email', $hotel->email) }}"></div>
                <div><label>City</label><input name="city" value="{{ old('city', $hotel->city) }}"></div>
                <div><label>GST number</label><input name="gst_number" value="{{ old('gst_number', $hotel->gst_number) }}"></div>
            </div>
            <div class="field" style="margin-top:14px"><label>Address</label><textarea name="address" rows="2">{{ old('address', $hotel->address) }}</textarea></div>
        </section>

        <section class="glass pad">
            <h2>Pricing, tax &amp; invoices</h2>
            <div class="form-grid">
                <div><label>Currency symbol</label><input name="currency_symbol" value="{{ old('currency_symbol', $hotel->currency_symbol) }}" required></div>
                <div><label>Tax percentage</label><input type="number" step="0.01" min="0" max="100" name="tax_percent" value="{{ old('tax_percent', $hotel->tax_percent) }}" required></div>
                <div><label>Invoice prefix</label><input name="invoice_prefix" value="{{ old('invoice_prefix', $hotel->invoice_prefix) }}" required></div>
                <div><label>Standard check-in time</label><input type="time" name="check_in_time" value="{{ old('check_in_time', $hotel->check_in_time) }}" required></div>
                <div><label>Standard check-out time</label><input type="time" name="check_out_time" value="{{ old('check_out_time', $hotel->check_out_time) }}" required></div>
            </div>
            <div class="field" style="margin-top:14px"><label>Invoice footer / terms</label><textarea name="invoice_footer" rows="3">{{ old('invoice_footer', $hotel->invoice_footer) }}</textarea></div>
            <p class="muted small">Room prices are set per room under Rooms; room-type base prices under Room types.</p>
            <button class="btn primary" type="submit">Save settings</button>
        </section>
    </form>
@endsection
