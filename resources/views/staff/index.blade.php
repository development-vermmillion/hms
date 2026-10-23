@extends('layouts.app')
@section('title', 'Staff')
@section('heading', 'Staff')

@section('content')
    <section class="glass pad">
        <h2>{{ $editing ? 'Edit '.$editing->name : 'Add a staff member' }}</h2>
        <form method="POST" action="{{ $editing ? route('staff.update', $editing) : route('staff.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <div class="form-grid">
                <div><label>Full name</label><input name="name" value="{{ old('name', $editing?->name) }}" required></div>
                <div><label>Staff ID (entered on every entry)</label><input name="employee_code" value="{{ old('employee_code', $editing?->employee_code) }}" required></div>
                <div><label>Login e-mail</label><input type="email" name="email" value="{{ old('email', $editing?->email) }}" required></div>
                <div><label>Phone</label><input name="phone" value="{{ old('phone', $editing?->phone) }}"></div>
                <div><label>{{ $editing ? 'New password (leave blank to keep)' : 'Password (min 8)' }}</label><input type="password" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}></div>
                @if($editing)
                    <div class="check" style="align-self:end"><input id="is_active" type="checkbox" name="is_active" value="1" @checked($editing->is_active)><label for="is_active">Can sign in</label></div>
                @endif
            </div>
            <div class="row" style="margin-top:16px">
                <button class="btn primary" type="submit">{{ $editing ? 'Save changes' : 'Add staff' }}</button>
                @if($editing)<a class="btn" href="{{ route('staff.index') }}">Cancel</a>@endif
            </div>
        </form>
    </section>

    <section class="glass pad">
        <div class="table-wrap"><table>
            <thead><tr><th>Name</th><th>Staff ID</th><th>Role</th><th>E-mail</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td><b>{{ $u->name }}</b><div class="muted small">{{ $u->phone }}</div></td>
                    <td>{{ $u->employee_code }}</td>
                    <td>{{ ucfirst($u->role) }}</td>
                    <td>{{ $u->email }}</td>
                    <td><x-pill :status="$u->is_active ? 'available' : 'cancelled'"/> <span class="muted small">{{ $u->is_active ? 'active' : 'disabled' }}</span></td>
                    <td><div class="row" style="justify-content:flex-end">
                        <a class="btn sm" href="{{ route('staff.index', ['edit' => $u->id]) }}">Edit</a>
                        @if($u->is_active && $u->id !== auth()->id())
                            <form class="inline-form" method="POST" action="{{ route('staff.destroy', $u) }}" onsubmit="return confirm('Disable {{ $u->name }}? Their past entries are kept.')">
                                @csrf @method('DELETE')<button class="btn sm danger" type="submit">Disable</button>
                            </form>
                        @endif
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </section>
@endsection
