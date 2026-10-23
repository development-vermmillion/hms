<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\Stay;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with(['type', 'currentStay.guest'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('room_type_id', $request->query('type')))
            ->orderBy('room_number')->get();

        return view('rooms.index', [
            'rooms' => $rooms,
            'types' => RoomType::orderBy('name')->get(),
            'editing' => $request->filled('edit') && $request->user()->isOwner() ? Room::find($request->query('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        Room::create($this->validated($request) + ['status' => Room::AVAILABLE]);

        return redirect()->route('rooms.index')->with('status', 'Room added.');
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));

        return redirect()->route('rooms.index')->with('status', "Room {$room->room_number} updated.");
    }

    public function destroy(Room $room)
    {
        if ($room->stays()->exists()) {
            return back()->withErrors(['delete' => 'This room has stay history and cannot be deleted. Put it in maintenance instead.']);
        }
        $room->delete();

        return redirect()->route('rooms.index')->with('status', 'Room deleted.');
    }

    /** Quick status change (staff and owner). Occupied/reserved are driven by stays, not set by hand. */
    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate(['status' => ['required', Rule::in([Room::AVAILABLE, Room::CLEANING, Room::MAINTENANCE])]]);
        $this->confirmStaff($request);

        if (in_array($room->status, [Room::OCCUPIED, Room::RESERVED], true)
            && Stay::where('room_id', $room->id)->whereIn('status', [Stay::CHECKED_IN, Stay::RESERVED])->exists()) {
            return back()->withErrors(['status' => 'Room has an active stay or reservation. Check out or cancel it first.']);
        }

        $room->update(['status' => $data['status']]);

        return back()->with('status', "Room {$room->room_number} is now {$data['status']}.");
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $hotelId = $request->user()->hotel_id;

        return $request->validate([
            'room_number' => ['required', 'string', 'max:20', Rule::unique('rooms')->where('hotel_id', $hotelId)->ignore($room?->id)],
            'room_type_id' => ['required', Rule::exists('room_types', 'id')->where('hotel_id', $hotelId)],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
