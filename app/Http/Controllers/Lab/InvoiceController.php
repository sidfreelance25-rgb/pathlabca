<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function pay(Request $request, Invoice $invoice, AuditRecorder $audit): RedirectResponse
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $audit->record($request->user(), 'invoice.paid', $invoice);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment recorded.']);

        return back();
    }
}
