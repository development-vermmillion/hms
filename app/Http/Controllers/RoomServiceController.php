<?php

namespace App\Http\Controllers;

use App\Models\FoodItem;
use App\Models\RoomServiceOrder;
use App\Models\Stay;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomServiceController extends Controller
{
    public function __construct(private BillingService $billing) {}

    /** Place a food order for a room. `items` is [food_item_id => qty]. */
    public function store(Request $request, Stay $stay)
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $this->confirmStaff($request);

        if ($stay->status !== Stay::CHECKED_IN) {
            return back()->withErrors(['stay' => 'Room service is only available while the guest is in house.']);
        }

        $lines = collect($request->input('items'))->filter(fn ($qty) => (int) $qty > 0);
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Choose at least one item.']);
        }

        $foods = FoodItem::whereIn('id', $lines->keys())->get()->keyBy('id');

        DB::transaction(function () use ($stay, $lines, $foods, $user, $request) {
            $order = RoomServiceOrder::create([
                'stay_id' => $stay->id,
                'notes' => $request->input('notes'),
                'created_by' => $user->id,
            ]);

            $total = 0;
            foreach ($lines as $foodId => $qty) {
                $food = $foods[$foodId] ?? null;
                if (! $food) {
                    continue;
                }
                $order->items()->create(['name' => $food->name, 'qty' => (int) $qty, 'price' => $food->price]);
                $total += (int) $qty * (float) $food->price;
            }
            $order->update(['total' => round($total, 2)]);
        });

        $this->billing->refresh($stay->fresh(), $user->id);

        return back()->with('status', 'Room service order placed.');
    }

    public function updateStatus(Request $request, RoomServiceOrder $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['delivered', 'cancelled'])]]);
        $user = $this->confirmStaff($request);

        if ($order->status !== 'placed') {
            return back()->withErrors(['order' => 'This order is already closed.']);
        }

        $order->update([
            'status' => $data['status'],
            'delivered_by' => $data['status'] === 'delivered' ? $user->id : null,
        ]);

        $this->billing->refresh($order->stay, $user->id);

        return back()->with('status', 'Order marked '.$data['status'].'.');
    }
}
