<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Stay;

class BillingService
{
    /**
     * Create the invoice for a stay if it does not exist, then recompute every
     * figure from the live data (room nights, room service, extras, amenity
     * penalties, tax, payments). Safe to call repeatedly.
     */
    public function refresh(Stay $stay, ?int $userId = null): Invoice
    {
        $stay->unsetRelation('invoice');
        $invoice = $stay->invoice ?? new Invoice([
            'hotel_id' => $stay->hotel_id,
            'stay_id' => $stay->id,
            'number' => $stay->hotel->nextInvoiceNumber(),
            'created_by' => $userId,
        ]);

        $room = $stay->roomCharges();
        $food = $stay->foodTotal();
        $extra = $stay->extrasTotal();
        $amenity = $stay->amenityPenalty();

        // Tax applies to services; amenity penalties are recoveries, not taxable supply.
        $tax = round(($room + $food + $extra) * ((float) $stay->hotel->tax_percent) / 100, 2);
        $discount = (float) ($invoice->discount ?? 0);
        $total = max(0, round($room + $food + $extra + $amenity + $tax - $discount, 2));
        $paid = $stay->paidTotal();
        $balance = max(0, round($total - $paid, 2));

        $invoice->fill([
            'room_charges' => $room,
            'food_charges' => $food,
            'extra_charges' => $extra,
            'amenity_charges' => $amenity,
            'tax_amount' => $tax,
            'discount' => $discount,
            'total' => $total,
            'paid' => $paid,
            'balance' => $balance,
            'status' => $paid <= 0 ? 'unpaid' : ($balance > 0 ? 'partial' : 'paid'),
        ])->save();

        // Link any payments taken before the invoice existed (e.g. advances).
        $stay->payments()->whereNull('invoice_id')->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }
}
