@extends('layouts.app')
@section('title', 'Guests')
@section('heading', 'Guests')

@section('actions')
    <a class="btn primary" href="{{ route('guests.create') }}">+ Add guest</a>
@endsection

@section('content')
    <form class="glass tight row" method="GET" action="{{ route('guests.index') }}">
        <input style="max-width:360px" type="search" name="q" value="{{ $search }}" placeholder="Search name, phone, e-mail or ID number">
        <button class="btn sm primary" type="submit">Search</button>
        @if($search !== '')<a class="btn sm" href="{{ route('guests.index') }}">Clear</a>@endif
    </form>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Name</th><th>Phone</th><th>E-mail</th><th>ID proof</th><th>Stays</th><th></th></tr></thead>
            <tbody>
            @forelse($guests as $guest)
                <tr>
                    <td><a href="{{ route('guests.show', $guest) }}">{{ $guest->name }}</a></td>
                    <td>{{ $guest->phone }}</td>
                    <td>{{ $guest->email ?: '—' }}</td>
                    <td>{{ $guest->id_proof_type ? $guest->id_proof_type.' · '.$guest->id_proof_number : '—' }}</td>
                    <td>{{ $guest->stays_count }}</td>
                    <td class="num"><a class="btn sm good" href="{{ route('stays.create', ['guest' => $guest->id]) }}">Book</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No guests found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="pagination">{{ $guests->links() }}</div>
    </section>
@endsection
