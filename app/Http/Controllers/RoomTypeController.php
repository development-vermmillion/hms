<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        return view('room-types.index', [
            'types' => RoomType::withCount('rooms')->orderBy('name')->get(),
            'editing' => $request->filled('edit') ? RoomType::find($request->query('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        RoomType::create($this->validated($request));

        return redirect()->route('room-types.index')->with('status', 'Room type added.');
    }

    public function update(Request $request, RoomType $roomType)
    {
        $roomType->update($this->validated($request, $roomType));

        return redirect()->route('room-types.index')->with('status', 'Room type updated.');
    }

    public function destroy(RoomType $roomType)
    {
        if ($roomType->rooms()->exists()) {
            return back()->withErrors(['delete' => 'Move or delete the rooms of this type first.']);
        }
        $roomType->delete();

        return back()->with('status', 'Room type deleted.');
    }

    private function validated(Request $request, ?RoomType $type = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('room_types')
                ->where('hotel_id', $request->user()->hotel_id)->ignore($type?->id)],
            'base_price' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
