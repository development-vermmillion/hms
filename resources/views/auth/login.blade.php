<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}">
</head>
<body>
<div class="bg" aria-hidden="true">
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span><span class="blob b4"></span>
</div>
<div class="login-wrap">
    <form class="glass login-card" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="brand" style="padding:0"><div class="mark">H</div><div><b>{{ config('app.name') }}</b><small>Owner &amp; staff sign in</small></div></div>
        <h1>Welcome back</h1>
        <p class="muted" style="margin:0 0 22px">Sign in with your work e-mail.</p>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <div class="check field"><input id="remember" type="checkbox" name="remember" value="1"><label for="remember">Keep me signed in on this device</label></div>
        <button class="btn primary block" type="submit">Sign in</button>
    </form>
</div>
</body>
</html>
