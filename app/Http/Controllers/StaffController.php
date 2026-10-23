<?php

namespace App\Http\Controllers;

use App\Models\Stay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $hotelId = $request->user()->hotel_id;

        return view('staff.index', [
            'users' => User::where('hotel_id', $hotelId)->orderByDesc('role')->orderBy('name')->get(),
            'editing' => $request->filled('edit') ? User::where('hotel_id', $hotelId)->find($request->query('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $hotelId = $request->user()->hotel_id;
        $data = $this->validated($request, $hotelId);

        User::create($data + ['hotel_id' => $hotelId, 'role' => User::STAFF, 'is_active' => true]);

        return redirect()->route('staff.index')->with('status', 'Staff member added.');
    }

    public function update(Request $request, User $staff)
    {
        $this->authorizeSameHotel($request, $staff);
        $data = $this->validated($request, $staff->hotel_id, $staff);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Owners cannot lock themselves out by disabling their own account.
        $active = $staff->id === $request->user()->id ? true : $request->boolean('is_active');

        $staff->update($data + ['is_active' => $active]);

        return redirect()->route('staff.index')->with('status', 'Staff member updated.');
    }

    /** Staff are never deleted: their ID stays attached to historic entries. Disable instead. */
    public function destroy(Request $request, User $staff)
    {
        $this->authorizeSameHotel($request, $staff);

        if ($staff->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'You cannot disable your own account.']);
        }

        $staff->update(['is_active' => false]);

        return back()->with('status', $staff->name.' can no longer sign in. Their past entries are kept.');
    }

    private function authorizeSameHotel(Request $request, User $staff): void
    {
        abort_unless($staff->hotel_id === $request->user()->hotel_id, 404);
    }

    private function validated(Request $request, int $hotelId, ?User $staff = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('users')->where('hotel_id', $hotelId)->ignore($staff?->id)],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }
}
