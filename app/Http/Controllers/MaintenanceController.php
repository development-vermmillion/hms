<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'active');

        $requests = MaintenanceRequest::with(['room', 'reporter', 'resolver'])
            ->when($status === 'active', fn ($q) => $q->whereIn('status', ['open', 'in_progress']))
            ->when($status === 'resolved', fn ($q) => $q->where('status', 'resolved'))
            ->latest()->paginate(20)->withQueryString();

        return view('maintenance.index', [
            'requests' => $requests,
            'status' => $status,
            'rooms' => Room::orderBy('room_number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->where('hotel_id', $request->user()->hotel_id)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'blocks_room' => ['nullable', 'boolean'],
        ]);
        $user = $this->confirmStaff($request);

        $block = $request->boolean('blocks_room') && ! empty($data['room_id']);
        $room = $block ? Room::find($data['room_id']) : null;

        if ($room && in_array($room->status, [Room::OCCUPIED, Room::RESERVED], true)) {
            return back()->withErrors(['blocks_room' => 'Room is occupied or reserved, so it cannot be blocked now.'])->withInput();
        }

        MaintenanceRequest::create([
            'room_id' => $data['room_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'blocks_room' => $block,
            'reported_by' => $user->id,
        ]);

        $room?->update(['status' => Room::MAINTENANCE]);

        return back()->with('status', 'Maintenance request logged.');
    }

    public function update(Request $request, MaintenanceRequest $maintenance)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['in_progress', 'resolved'])]]);
        $user = $this->confirmStaff($request);

        $maintenance->update([
            'status' => $data['status'],
            'resolved_by' => $data['status'] === 'resolved' ? $user->id : null,
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        // Release the room once nothing else is blocking it; housekeeping then confirms readiness.
        if ($data['status'] === 'resolved' && $maintenance->room && $maintenance->room->status === Room::MAINTENANCE
            && ! MaintenanceRequest::where('room_id', $maintenance->room_id)->where('blocks_room', true)
                ->whereIn('status', ['open', 'in_progress'])->exists()) {
            $maintenance->room->update(['status' => Room::CLEANING]);

            return back()->with('status', 'Resolved. Room moved to cleaning so housekeeping can confirm it is ready.');
        }

        return back()->with('status', 'Request updated.');
    }
}
