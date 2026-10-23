@extends('layouts.app')
@section('title', $title)
@section('heading', $title)

@section('actions')
    <a class="btn" href="{{ route('reports.show', [$type, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'export' => 'csv']) }}">Download CSV</a>
    <button class="btn primary" type="button" onclick="window.print()">Print</button>
@endsection

@section('content')
    <form class="glass tight row no-print" method="GET" action="{{ route('reports.show', $type) }}">
        <div><label>From</label><input type="date" name="from" value="{{ $from->toDateString() }}"></div>
        <div><label>To</label><input type="date" name="to" value="{{ $to->toDateString() }}"></div>
        <div style="align-self:end"><button class="btn primary" type="submit">Run report</button></div>
        <div style="align-self:end" class="row">
            <a class="btn sm" href="{{ route('reports.show', [$type, 'from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">Today</a>
            <a class="btn sm" href="{{ route('reports.show', [$type, 'from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]) }}">This month</a>
            <a class="btn sm" href="{{ route('reports.show', [$type, 'from' => today()->subMonth()->startOfMonth()->toDateString(), 'to' => today()->subMonth()->endOfMonth()->toDateString()]) }}">Last month</a>
        </div>
    </form>

    <div class="grid g-4">
        @foreach($summary as $label => $value)
            <div class="glass stat"><div class="label">{{ $label }}</div><div class="value" style="font-size:26px">{{ $value }}</div></div>
        @endforeach
    </div>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr>@foreach($columns as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="empty">No data for this period.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <p class="muted small" style="margin-bottom:0">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} · {{ count($rows) }} row(s)</p>
    </section>
@endsection
