<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ auth()->user()->hotel->name ?? config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}">
</head>
<body>
<div class="bg" aria-hidden="true">
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span><span class="blob b4"></span>
</div>

@php($me = auth()->user())
<div class="shell">
    <aside class="sidebar glass" id="sidebar">
        <div class="brand">
            <div class="mark">{{ strtoupper(substr($me->hotel->name, 0, 1)) }}</div>
            <div><b>{{ $me->hotel->name }}</b><small>Hotel management</small></div>
        </div>

        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="home"/> Dashboard</a>
            <a href="{{ route('rooms.index') }}" class="{{ request()->routeIs('rooms.*') ? 'active' : '' }}"><x-icon name="rooms"/> Rooms</a>
            <a href="{{ route('stays.index') }}" class="{{ request()->routeIs('stays.*') ? 'active' : '' }}"><x-icon name="calendar"/> Stays &amp; bookings</a>
            <a href="{{ route('guests.index') }}" class="{{ request()->routeIs('guests.*') ? 'active' : '' }}"><x-icon name="users"/> Guests</a>
            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}"><x-icon name="invoice"/> Billing</a>
            <a href="{{ route('housekeeping.index') }}" class="{{ request()->routeIs('housekeeping.*') ? 'active' : '' }}"><x-icon name="clean"/> Housekeeping</a>
            <a href="{{ route('maintenance.index') }}" class="{{ request()->routeIs('maintenance.*') ? 'active' : '' }}"><x-icon name="tool"/> Maintenance</a>

            @if($me->isOwner())
                <div class="nav-group">Owner</div>
                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><x-icon name="chart"/> Reports</a>
                <a href="{{ route('room-types.index') }}" class="{{ request()->routeIs('room-types.*') ? 'active' : '' }}"><x-icon name="tag"/> Room types</a>
                <a href="{{ route('catalog.index', 'food') }}" class="{{ request()->is('catalog/food*') ? 'active' : '' }}"><x-icon name="food"/> Room service menu</a>
                <a href="{{ route('catalog.index', 'amenities') }}" class="{{ request()->is('catalog/amenities*') ? 'active' : '' }}"><x-icon name="box"/> Amenities</a>
                <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}"><x-icon name="badge"/> Staff</a>
                <a href="{{ route('settings.edit') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}"><x-icon name="sliders"/> Settings</a>
            @endif
        </nav>

        <div class="me">
            <b>{{ $me->name }}</b>
            <small>{{ ucfirst($me->role) }} · ID {{ $me->employee_code }}</small>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn sm block" type="submit"><x-icon name="logout" :size="16"/> Sign out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar glass">
            <div class="row">
                <button class="btn sm menu-toggle" type="button" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">☰</button>
                <div>
                    <h1>@yield('heading', 'Dashboard')</h1>
                    <div class="sub">{{ now()->format('l, j F Y') }}</div>
                </div>
            </div>
            <div class="row no-print">@yield('actions')</div>
        </header>

        @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="flash err">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script>
    // Close the mobile menu when tapping outside it.
    document.addEventListener('click', function (e) {
        if (document.body.classList.contains('nav-open') && !e.target.closest('#sidebar') && !e.target.closest('.menu-toggle')) {
            document.body.classList.remove('nav-open');
        }
    });
</script>
@stack('scripts')
</body>
</html>
