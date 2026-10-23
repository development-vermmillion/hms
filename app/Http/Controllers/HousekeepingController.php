<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    public function index()
    {
        $hotelId = auth()->user()->hotel_id;

        return view('housekeeping.index', [
            'open' => HousekeepingTask::with(['room.type', 'assignee', 'creator'])
                ->whereIn('status', ['pending', 'in_progress'])->orderBy('created_at')->get(),
            'recent' => HousekeepingTask::with(['room', 'assignee', 'completedBy'])
                ->where('status', 'done')->latest('completed_at')->limit(15)->get(),
            'rooms' => Room::orderBy('room_number')->get(),
            'cleaningRooms' => Room::where('status', Room::CLEANING)->orderBy('room_number')->get(),
            'team' => User::where('hotel_id', $hotelId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $hotelId = $request->user()->hotel_id;

        $data = $request->validate([
            'room_id' => ['required', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('hotel_id', $hotelId)],
            'type' => ['required', Rule::in(['cleaning', 'checkout_cleaning', 'turndown', 'deep_clean', 'inspection'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $this->confirmStaff($request);

        HousekeepingTask::create($data + ['status' => 'pending', 'created_by' => $user->id]);

        return back()->with('status', 'Task assigned.');
    }

    public function update(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['in_progress', 'done'])]]);
        $user = $this->confirmStaff($request);

        $task->update([
            'status' => $data['status'],
            'completed_by' => $data['status'] === 'done' ? $user->id : null,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        // Finishing a cleaning task makes the room ready again (room readiness update).
        if ($data['status'] === 'done' && $task->room->status === Room::CLEANING
            && ! HousekeepingTask::where('room_id', $task->room_id)->whereIn('status', ['pending', 'in_progress'])->exists()) {
            $task->room->update(['status' => Room::AVAILABLE]);

            return back()->with('status', "Room {$task->room->room_number} is clean and available.");
        }

        return back()->with('status', 'Task updated.');
    }
}
