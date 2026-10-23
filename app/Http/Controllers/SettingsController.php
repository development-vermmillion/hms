<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings.edit', ['hotel' => $request->user()->hotel]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:40'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
        ]);

        $request->user()->hotel->update($data);

        return back()->with('status', 'Settings saved. New tax and invoice settings apply the next time an invoice is recalculated.');
    }
}
