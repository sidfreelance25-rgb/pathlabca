<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Specimen;
use App\Services\BarcodeService;
use App\Services\SpecimenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpecimenController extends Controller
{
    public function collection(): Response
    {
        return Inertia::render('lab/collection/index', [
            'specimens' => Specimen::query()
                ->with('specimenType', 'order.patient')
                ->whereIn('status', ['pending', 'collected'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function collect(Request $request, Specimen $specimen, SpecimenService $specimens): RedirectResponse
    {
        $data = $request->validate([
            'collection_site' => ['nullable', 'string', 'max:80'],
            'condition' => ['nullable', 'string', 'max:80'],
        ]);
        $user = $request->user();
        abort_if($user === null, 403);
        $specimens->collect($specimen, $user, $data['collection_site'] ?? null, $data['condition'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Specimen collected.']);

        return back();
    }

    public function label(Specimen $specimen, BarcodeService $barcodes): Response
    {
        $specimen->load('specimenType', 'order.patient', 'order.branch.lab');

        return Inertia::render('lab/collection/label', [
            'specimen' => $specimen,
            'barcode' => $barcodes->code128DataUri($specimen->barcode),
        ]);
    }

    public function lookup(): Response
    {
        return Inertia::render('lab/specimens/scan', ['match' => null]);
    }

    public function scan(Request $request): Response
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:40'],
        ]);

        $specimen = Specimen::query()
            ->with('specimenType', 'order.patient', 'order.items.test', 'events.user:id,name')
            ->where('barcode', $data['barcode'])
            ->first();

        return Inertia::render('lab/specimens/scan', [
            'match' => $specimen,
            'barcode' => $data['barcode'],
        ]);
    }

    public function receive(Request $request, Specimen $specimen, SpecimenService $specimens): RedirectResponse
    {
        $data = $request->validate([
            'condition' => ['nullable', 'string', 'max:80'],
        ]);
        $user = $request->user();
        abort_if($user === null, 403);
        $specimens->receive($specimen, $user, $data['condition'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Specimen accessioned.']);

        return back();
    }
}
