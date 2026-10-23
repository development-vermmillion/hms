<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Every entry made by staff carries their staff ID. The ID typed in the
     * form must match the logged-in account, so records can be traced back to
     * the employee who served the guest.
     */
    protected function confirmStaff(Request $request): User
    {
        $request->validate(['staff_code' => ['required', 'string', 'max:30']]);

        $user = $request->user();

        if (strcasecmp(trim($request->input('staff_code')), $user->employee_code) !== 0) {
            throw ValidationException::withMessages([
                'staff_code' => 'Staff ID does not match the logged-in account.',
            ]);
        }

        return $user;
    }
}
