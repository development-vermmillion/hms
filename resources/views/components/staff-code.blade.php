@props(['uid' => 'f'])
{{--
    The logged-in employee's ID is filled in automatically, so nobody has to type it.
    The server still checks it against the logged-in account (Controller::confirmStaff),
    so it cannot be changed to someone else's ID.
--}}
<div class="staff-id">
    <label for="staff_code_{{ $uid }}">Recorded under staff ID</label>
    <input id="staff_code_{{ $uid }}" type="text" name="staff_code" value="{{ auth()->user()->employee_code }}" readonly tabindex="-1">
    @error('staff_code')<div class="error">{{ $message }}</div>@enderror
</div>
