<?php

namespace App\Http\Controllers;

use App\Models\AmenityItem;
use App\Models\FoodItem;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Stay;
use App\Models\StayAmenity;
use App\Models\StayCharge;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StayController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $stays = Stay::with(['guest', 'room.type', 'invoice'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('guest', fn ($g) => $g->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('room', fn ($r) => $r->where('room_number', 'like', "%{$search}%"))))
            ->latest('id')->paginate(20)->withQueryString();

        return view('stays.index', compact('stays', 'status', 'search'));
    }

    public function create(Request $request)
    {
        return view('stays.create', [
            'rooms' => Room::with('type')->where('status', Room::AVAILABLE)->orderBy('room_number')->get(),
            'guests' => Guest::orderBy('name')->get(['id', 'name', 'phone']),
            'selectedRoom' => $request->query('room'),
            'selectedGuest' => $request->query('guest'),
        ]);
    }

    public function store(Request $request)
    {
        $hotelId = $request->user()->hotel_id;

        $data = $request->validate([
            'guest_id' => ['nullable', Rule::exists('guests', 'id')->where('hotel_id', $hotelId)],
            'guest_name' => ['required_without:guest_id', 'nullable', 'string', 'max:120'],
            'guest_phone' => ['required_without:guest_id', 'nullable', 'string', 'max:30'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_address' => ['nullable', 'string', 'max:500'],
            'id_proof_type' => ['nullable', 'string', 'max:40'],
            'id_proof_number' => ['nullable', 'string', 'max:60'],
            'room_id' => ['required', Rule::exists('rooms', 'id')->where('hotel_id', $hotelId)],
            'arrival_date' => ['required', 'date'],
            'expected_check_out' => ['required', 'date', 'after:arrival_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'rate' => ['required', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', Rule::in(['check_in', 'reserve'])],
        ]);
        $user = $this->confirmStaff($request);

        $room = Room::findOrFail($data['room_id']);
        if ($room->status !== Room::AVAILABLE) {
            throw ValidationException::withMessages(['room_id' => "Room {$room->room_number} is {$room->status}, not available."]);
        }

        $stay = DB::transaction(function () use ($data, $user, $room) {
            $guest = ! empty($data['guest_id'])
                ? Guest::findOrFail($data['guest_id'])
                : Guest::create([
                    'name' => $data['guest_name'],
                    'phone' => $data['guest_phone'],
                    'email' => $data['guest_email'] ?? null,
                    'address' => $data['guest_address'] ?? null,
                    'id_proof_type' => $data['id_proof_type'] ?? null,
                    'id_proof_number' => $data['id_proof_number'] ?? null,
                    'created_by' => $user->id,
                ]);

            $checkIn = $data['action'] === 'check_in';

            if ($checkIn && empty($guest->id_proof_number)) {
                throw ValidationException::withMessages(['id_proof_number' => 'ID proof details are required to check a guest in.']);
            }

            $stay = Stay::create([
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'status' => $checkIn ? Stay::CHECKED_IN : Stay::RESERVED,
                'arrival_date' => $data['arrival_date'],
                'check_in_at' => $checkIn ? now() : null,
                'expected_check_out' => $data['expected_check_out'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'rate' => $data['rate'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'checked_in_by' => $checkIn ? $user->id : null,
            ]);

            $room->update(['status' => $checkIn ? Room::OCCUPIED : Room::RESERVED]);

            if (! empty($data['advance']) && (float) $data['advance'] > 0) {
                Payment::create([
                    'stay_id' => $stay->id,
                    'amount' => $data['advance'],
                    'kind' => 'advance',
                    'note' => 'Advance at '.($checkIn ? 'check-in' : 'booking'),
                    'received_by' => $user->id,
                    'paid_at' => now(),
                ]);
            }

            return $stay;
        });

        return redirect()->route('stays.show', $stay)
            ->with('status', $stay->status === Stay::CHECKED_IN ? 'Guest checked in.' : 'Room reserved.');
    }

    public function show(Stay $stay)
    {
        $stay->load([
            'guest', 'room.type', 'invoice', 'payments.receiver', 'orders.items', 'orders.creator',
            'charges.creator', 'amenities.item', 'amenities.issuer', 'amenities.checker',
            'creator', 'checkedInBy', 'checkedOutBy', 'cancelledBy',
        ]);

        return view('stays.show', [
            'stay' => $stay,
            'foodItems' => FoodItem::where('is_active', true)->orderBy('name')->get(),
            'amenityItems' => AmenityItem::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** Reserved -> checked in. */
    public function checkIn(Request $request, Stay $stay)
    {
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::RESERVED) {
            return back()->withErrors(['stay' => 'Only reserved stays can be checked in.']);
        }
        if (empty($stay->guest->id_proof_number)) {
            return back()->withErrors(['stay' => 'Add the guest\'s ID proof before check-in.']);
        }

        DB::transaction(function () use ($stay, $user) {
            $stay->update(['status' => Stay::CHECKED_IN, 'check_in_at' => now(), 'checked_in_by' => $user->id]);
            $stay->room->update(['status' => Room::OCCUPIED]);
        });

        return back()->with('status', 'Guest checked in.');
    }

    public function cancel(Request $request, Stay $stay)
    {
        $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::RESERVED) {
            return back()->withErrors(['stay' => 'Only reservations that have not checked in can be cancelled.']);
        }

        DB::transaction(function () use ($request, $stay, $user) {
            $stay->update([
                'status' => Stay::CANCELLED,
                'cancel_reason' => $request->input('cancel_reason'),
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
            ]);
            $stay->room->update(['status' => Room::AVAILABLE]);
        });

        return back()->with('status', 'Reservation cancelled. Any advance paid stays on record for settlement.');
    }

    public function addCharge(Request $request, Stay $stay)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::CHECKED_IN) {
            return back()->withErrors(['stay' => 'Charges can only be added while the guest is in house.']);
        }

        StayCharge::create($data + ['stay_id' => $stay->id, 'created_by' => $user->id]);
        $this->billing->refresh($stay->fresh(), $user->id);

        return back()->with('status', 'Charge added.');
    }

    public function issueAmenity(Request $request, Stay $stay)
    {
        $data = $request->validate([
            'amenity_item_id' => ['required', Rule::exists('amenity_items', 'id')->where('hotel_id', $request->user()->hotel_id)],
            'qty_issued' => ['required', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::CHECKED_IN) {
            return back()->withErrors(['stay' => 'Items can only be issued while the guest is in house.']);
        }

        StayAmenity::create($data + ['stay_id' => $stay->id, 'issued_by' => $user->id]);

        return back()->with('status', 'Item issued to the room.');
    }

    public function checkoutForm(Stay $stay)
    {
        if ($stay->status !== Stay::CHECKED_IN) {
            return redirect()->route('stays.show', $stay)->withErrors(['stay' => 'This stay is not checked in.']);
        }

        $stay->load(['guest', 'room.type', 'amenities.item', 'orders']);

        return view('stays.checkout', [
            'stay' => $stay,
            'preview' => [
                'nights' => $stay->nights(),
                'room' => $stay->roomCharges(),
                'food' => $stay->foodTotal(),
                'extra' => $stay->extrasTotal(),
                'paid' => $stay->paidTotal(),
            ],
        ]);
    }

    /** Check-out: verify returned items, bill penalties, free the room for cleaning, issue invoice. */
    public function checkout(Request $request, Stay $stay)
    {
        $request->validate([
            'returns' => ['nullable', 'array'],
            'returns.*' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::CHECKED_IN) {
            return back()->withErrors(['stay' => 'This stay is not checked in.']);
        }

        $invoice = DB::transaction(function () use ($request, $stay, $user) {
            foreach ($stay->amenities()->with('item')->get() as $amenity) {
                $returned = min((int) $request->input("returns.{$amenity->id}", $amenity->qty_issued), $amenity->qty_issued);
                $missing = $amenity->qty_issued - $returned;

                $amenity->update([
                    'qty_returned' => $returned,
                    'penalty_amount' => round($missing * (float) $amenity->item->replacement_price, 2),
                    'checked_by' => $user->id,
                    'checked_at' => now(),
                ]);
            }

            $stay->update(['status' => Stay::CHECKED_OUT, 'check_out_at' => now(), 'checked_out_by' => $user->id]);
            $stay->room->update(['status' => Room::CLEANING]);

            HousekeepingTask::create([
                'room_id' => $stay->room_id,
                'type' => 'checkout_cleaning',
                'status' => 'pending',
                'notes' => 'Room vacated after check-out.',
                'created_by' => $user->id,
            ]);

            return $this->billing->refresh($stay->fresh(), $user->id);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Guest checked out. Collect any outstanding balance below.');
    }
}
