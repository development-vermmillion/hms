<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $guests = Guest::withCount('stays')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('id_proof_number', 'like', "%{$search}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('guests.index', compact('guests', 'search'));
    }

    public function create()
    {
        return view('guests.form', ['guest' => new Guest]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = $this->confirmStaff($request);

        $guest = Guest::create($data + ['created_by' => $user->id]);

        return redirect()->route('guests.show', $guest)->with('status', 'Guest saved.');
    }

    public function show(Guest $guest)
    {
        $guest->load(['stays' => fn ($q) => $q->with(['room.type', 'invoice'])->latest('arrival_date')]);

        return view('guests.show', compact('guest'));
    }

    public function edit(Guest $guest)
    {
        return view('guests.form', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $data = $this->validated($request);
        $this->confirmStaff($request);

        $guest->update($data);

        return redirect()->route('guests.show', $guest)->with('status', 'Guest updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'id_proof_type' => ['nullable', 'string', 'max:40'],
            'id_proof_number' => ['nullable', 'string', 'max:60'],
        ]);
    }
}
