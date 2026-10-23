@extends('layouts.app')
@php($editing = $guest->exists)
@section('title', $editing ? 'Edit guest' : 'Add guest')
@section('heading', $editing ? 'Edit '.$guest->name : 'Add guest')

@section('content')
    <section class="glass pad">
        <form method="POST" action="{{ $editing ? route('guests.update', $guest) : route('guests.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <div class="form-grid">
                <div><label>Full name</label><input name="name" value="{{ old('name', $guest->name) }}" required></div>
                <div><label>Phone</label><input name="phone" value="{{ old('phone', $guest->phone) }}" required></div>
                <div><label>E-mail</label><input type="email" name="email" value="{{ old('email', $guest->email) }}"></div>
                <div><label>ID proof type</label>
                    <select name="id_proof_type">
                        <option value="">—</option>
                        @foreach(['Aadhaar', 'Passport', 'Driving licence', 'Voter ID', 'PAN', 'Other'] as $t)
                            <option @selected(old('id_proof_type', $guest->id_proof_type) === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>ID proof number</label><input name="id_proof_number" value="{{ old('id_proof_number', $guest->id_proof_number) }}"></div>
            </div>
            <div class="field" style="margin-top:14px"><label>Address</label><textarea name="address" rows="3">{{ old('address', $guest->address) }}</textarea></div>
            <div class="grid g-3" style="align-items:end">
                <x-staff-code uid="guest"/>
                <div class="row"><button class="btn primary" type="submit">Save guest</button><a class="btn" href="{{ $editing ? route('guests.show', $guest) : route('guests.index') }}">Cancel</a></div>
            </div>
        </form>
    </section>
@endsection
