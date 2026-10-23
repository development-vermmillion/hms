<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Stay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public const TYPES = [
        'revenue-daily' => ['Daily revenue', 'Money received each day'],
        'revenue-monthly' => ['Monthly revenue', 'Money received per month'],
        'occupancy' => ['Occupancy', 'Occupied rooms vs total rooms per day'],
        'bookings' => ['Bookings & cancellations', 'Reservations and walk-ins created in the period'],
        'payments' => ['Payments & outstanding', 'Invoices with amounts paid and pending'],
        'movements' => ['Check-ins & check-outs', 'Who arrived and left, and which staff handled it'],
    ];

    public function index()
    {
        return view('reports.index', ['types' => self::TYPES]);
    }

    public function show(Request $request, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $from = Carbon::parse($request->query('from', today()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()))->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $report = match ($type) {
            'revenue-daily' => $this->revenue($from, $to, 'Y-m-d', 'Date'),
            'revenue-monthly' => $this->revenue($from, $to, 'Y-m', 'Month'),
            'occupancy' => $this->occupancy($from, $to),
            'bookings' => $this->bookings($from, $to),
            'payments' => $this->payments($from, $to),
            'movements' => $this->movements($from, $to),
        };

        if ($request->query('export') === 'csv') {
            return $this->csv($type, $report);
        }

        return view('reports.show', [
            'type' => $type,
            'title' => self::TYPES[$type][0],
            'from' => $from,
            'to' => $to,
        ] + $report);
    }

    private function revenue(Carbon $from, Carbon $to, string $format, string $label): array
    {
        $payments = Payment::with('receiver')->whereBetween('paid_at', [$from, $to])->orderBy('paid_at')->get()
            ->groupBy(fn ($p) => $p->paid_at->format($format));

        $rows = $payments->map(fn ($group, $key) => [
            $key, $group->count(), money($group->sum('amount')),
        ])->values()->all();

        $total = $payments->flatten()->sum('amount');

        return [
            'summary' => ['Total received' => money($total), 'Payments' => $payments->flatten()->count()],
            'columns' => [$label, 'Payments', 'Amount received'],
            'rows' => $rows,
        ];
    }

    private function occupancy(Carbon $from, Carbon $to): array
    {
        $to = $to->copy()->min($from->copy()->addDays(92)->endOfDay()); // keep the report quick
        $totalRooms = Room::count();

        $stays = Stay::whereNotNull('check_in_at')->where('status', '!=', Stay::CANCELLED)
            ->where('check_in_at', '<=', $to)
            ->where(fn ($q) => $q->whereNull('check_out_at')->orWhere('check_out_at', '>=', $from))
            ->get(['id', 'room_id', 'check_in_at', 'check_out_at']);

        $rows = [];
        $sum = 0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $occupied = $stays->filter(fn ($s) => $s->check_in_at <= $dayEnd
                && ($s->check_out_at === null || $s->check_out_at >= $dayStart))->pluck('room_id')->unique()->count();
            $pct = $totalRooms ? round($occupied / $totalRooms * 100, 1) : 0;
            $sum += $pct;
            $rows[] = [$day->format('Y-m-d (D)'), $occupied, $totalRooms, $pct.' %'];
        }

        return [
            'summary' => ['Average occupancy' => (count($rows) ? round($sum / count($rows), 1) : 0).' %', 'Rooms' => $totalRooms],
            'columns' => ['Date', 'Occupied rooms', 'Total rooms', 'Occupancy'],
            'rows' => $rows,
        ];
    }

    private function bookings(Carbon $from, Carbon $to): array
    {
        $stays = Stay::with(['guest', 'room', 'creator', 'cancelledBy'])->whereBetween('created_at', [$from, $to])->latest()->get();

        return [
            'summary' => [
                'Bookings' => $stays->where('status', '!=', Stay::CANCELLED)->count(),
                'Cancellations' => $stays->where('status', Stay::CANCELLED)->count(),
                'Reserved' => $stays->where('status', Stay::RESERVED)->count(),
            ],
            'columns' => ['Created', 'Guest', 'Room', 'Arrival', 'Status', 'Booked by', 'Cancel reason', 'Cancelled by'],
            'rows' => $stays->map(fn ($s) => [
                $s->created_at->format('Y-m-d H:i'), $s->guest->name, $s->room->room_number,
                $s->arrival_date->format('Y-m-d'), str_replace('_', ' ', $s->status),
                $s->creator?->name.' ('.$s->creator?->employee_code.')',
                $s->cancel_reason ?? '', $s->cancelledBy?->name ?? '',
            ])->all(),
        ];
    }

    private function payments(Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::with('stay.guest')->whereBetween('created_at', [$from, $to])->latest()->get();

        return [
            'summary' => [
                'Billed' => money($invoices->sum('total')),
                'Collected' => money($invoices->sum('paid')),
                'Outstanding' => money($invoices->sum('balance')),
            ],
            'columns' => ['Invoice', 'Date', 'Guest', 'Total', 'Paid', 'Balance', 'Status'],
            'rows' => $invoices->map(fn ($i) => [
                $i->number, $i->created_at->format('Y-m-d'), $i->stay->guest->name,
                money($i->total), money($i->paid), money($i->balance), $i->status,
            ])->all(),
        ];
    }

    private function movements(Carbon $from, Carbon $to): array
    {
        $ins = Stay::with(['guest', 'room', 'checkedInBy'])->whereBetween('check_in_at', [$from, $to])->get()
            ->map(fn ($s) => [$s->check_in_at, 'Check-in', $s->guest->name, $s->room->room_number, $s->checkedInBy]);
        $outs = Stay::with(['guest', 'room', 'checkedOutBy'])->whereBetween('check_out_at', [$from, $to])->get()
            ->map(fn ($s) => [$s->check_out_at, 'Check-out', $s->guest->name, $s->room->room_number, $s->checkedOutBy]);

        $all = $ins->concat($outs)->sortBy(fn ($r) => $r[0]->timestamp)->values();

        return [
            'summary' => ['Check-ins' => $ins->count(), 'Check-outs' => $outs->count()],
            'columns' => ['When', 'Type', 'Guest', 'Room', 'Handled by'],
            'rows' => $all->map(fn ($r) => [
                $r[0]->format('Y-m-d H:i'), $r[1], $r[2], $r[3], $r[4] ? $r[4]->name.' ('.$r[4]->employee_code.')' : '',
            ])->all(),
        ];
    }

    private function csv(string $type, array $report)
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $report['columns']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "hms-{$type}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
