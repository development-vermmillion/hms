@extends('layouts.app')
@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')
    <div class="grid g-3">
        @foreach($types as $slug => [$title, $blurb])
            <a class="glass pad stat" href="{{ route('reports.show', $slug) }}" style="text-decoration:none;color:inherit">
                <div class="label"><x-icon name="chart"/> Report</div>
                <div class="value" style="font-size:20px">{{ $title }}</div>
                <div class="hint">{{ $blurb }}</div>
            </a>
        @endforeach
    </div>
@endsection
