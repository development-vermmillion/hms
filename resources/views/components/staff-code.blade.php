@props(['uid' => 'f'])
{{-- Every entry must carry the staff member's ID so work can be traced to the employee. --}}
<div class="staff-id">
    <label for="staff_code_{{ $uid }}">Your staff ID (required)</label>
    <input id="staff_code_{{ $uid }}" type="text" name="staff_code" value="{{ old('staff_code') }}"
           placeholder="e.g. {{ auth()->user()->employee_code }}" autocomplete="off" required>
    @error('staff_code')<div class="error">{{ $message }}</div>@enderror
</div>
