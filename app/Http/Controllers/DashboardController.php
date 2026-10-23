<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Stay;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        $byStatus = Room::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $stats = [
            'total_rooms' => (int) $byStatus->sum(),
            'available' => (int) ($byStatus[Room::AVAILABLE] ?? 0),
            'occupied' => (int) ($byStatus[Room::OCCUPIED] ?? 0),
            'reserved' => (int) ($byStatus[Room::RESERVED] ?? 0),
            'cleaning' => (int) ($byStatus[Room::CLEANING] ?? 0),
            'maintenance' => (int) ($byStatus[Room::MAINTENANCE] ?? 0),
            'checkins_today' => Stay::whereDate('check_in_at', $today)->count(),
            'checkouts_today' => Stay::whereDate('check_out_at', $today)->count(),
            'bookings_today' => Stay::whereDate('created_at', $today)->count(),
            'revenue_today' => (float) Payment::whereDate('paid_at', $today)->sum('amount'),
            'pending_amount' => (float) Invoice::where('balance', '>', 0)->sum('balance'),
            'pending_count' => Invoice::where('balance', '>', 0)->count(),
        ];

        $arrivals = Stay::with(['guest', 'room'])
            ->where('status', Stay::RESERVED)->whereDate('arrival_date', '<=', $today)->orderBy('arrival_date')->get();

        $departures = Stay::with(['guest', 'room'])
            ->where('status', Stay::CHECKED_IN)->whereDate('expected_check_out', '<=', $today)->orderBy('expected_check_out')->get();

        // Last 7 days of money received, for the chart.
        $paid = Payment::whereDate('paid_at', '>=', $today->copy()->subDays(6))->get()
            ->groupBy(fn ($p) => $p->paid_at->toDateString());
        $chart = collect(range(6, 0))->map(function ($d) use ($today, $paid) {
            $date = $today->copy()->subDays($d);

            return ['label' => $date->format('D'), 'value' => (float) ($paid[$date->toDateString()] ?? collect())->sum('amount')];
        });

        return view('dashboard', [
            'stats' => $stats,
            'rooms' => Room::with('type')->orderBy('room_number')->get(),
            'arrivals' => $arrivals,
            'departures' => $departures,
            'chart' => $chart,
            'openHousekeeping' => HousekeepingTask::whereIn('status', ['pending', 'in_progress'])->count(),
            'openMaintenance' => MaintenanceRequest::whereIn('status', ['open', 'in_progress'])->count(),
        ]);
    }
}
