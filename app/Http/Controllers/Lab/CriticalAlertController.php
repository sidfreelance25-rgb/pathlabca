<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Services\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CriticalAlertController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('lab/critical/index', [
            'alerts' => CriticalAlert::query()
                ->with('result.definition', 'result.orderItem.order.patient')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function acknowledge(Request $request, CriticalAlert $alert, ResultService $results): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);
        $user = $request->user();
        abort_if($user === null, 403);
        $results->acknowledgeCritical($alert, $user, $data['note']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Critical result acknowledged.']);

        return back();
    }
}
