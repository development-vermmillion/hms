@props(['status'])
<span class="pill {{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
