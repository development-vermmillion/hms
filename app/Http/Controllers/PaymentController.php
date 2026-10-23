<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Stay;
use App\Services\BillingService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing) {}

    /** Record money received against a stay (advance or settlement). No payment method is tracked. */
    public function store(Request $request, Stay $stay)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'kind' => ['nullable', 'in:advance,payment'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $this->confirmStaff($request);

        if ($stay->status === Stay::CANCELLED) {
            return back()->withErrors(['amount' => 'This stay was cancelled.']);
        }

        Payment::create([
            'stay_id' => $stay->id,
            'invoice_id' => $stay->invoice?->id,
            'amount' => $data['amount'],
            'kind' => $data['kind'] ?? 'payment',
            'note' => $data['note'] ?? null,
            'received_by' => $user->id,
            'paid_at' => now(),
        ]);

        // Keep invoice totals and payment status in step.
        $invoice = $stay->invoice ? $this->billing->refresh($stay->fresh(), $user->id) : null;

        return $invoice
            ? redirect()->route('invoices.show', $invoice)->with('status', 'Payment recorded.')
            : back()->with('status', 'Payment recorded.');
    }
}
