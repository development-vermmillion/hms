<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Stay;
use App\Services\BillingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $invoices = Invoice::with(['stay.guest', 'stay.room'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('stay.guest', fn ($g) => $g->where('name', 'like', "%{$search}%"))))
            ->latest('id')->paginate(20)->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'status' => $status,
            'search' => $search,
            'outstanding' => (float) Invoice::where('balance', '>', 0)->sum('balance'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'stay.guest', 'stay.room.type', 'stay.orders.items', 'stay.charges', 'stay.amenities.item',
            'stay.payments.receiver', 'stay.checkedOutBy', 'creator', 'hotel',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    /** Create or recalculate the invoice for a stay. */
    public function generate(Request $request, Stay $stay)
    {
        $user = $this->confirmStaff($request);
        $invoice = $this->billing->refresh($stay, $user->id);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    /** Owner only: apply a discount, then recompute. */
    public function discount(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['discount' => ['required', 'numeric', 'min:0']]);

        $invoice->update(['discount' => $data['discount']]);
        $this->billing->refresh($invoice->stay, $request->user()->id);

        return back()->with('status', 'Discount applied.');
    }
}
