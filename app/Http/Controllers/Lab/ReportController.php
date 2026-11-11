<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Order;
use App\Models\ReportTemplate;
use App\Models\ReportVersion;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('lab/reports/index', [
            'reports' => ReportVersion::query()
                ->with('order.patient:id,name,patient_number,is_demo')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(ReportVersion $report): Response
    {
        $report->load('order.patient', 'events.user:id,name', 'template');

        return Inertia::render('lab/reports/show', [
            'report' => $report,
        ]);
    }

    public function preview(Request $request, Order $order, ReportService $reports): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $report = $reports->preview($order, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft report generated.']);

        return redirect()->route('reports.show', $report);
    }

    public function release(Request $request, Order $order, ReportService $reports): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $report = $reports->release($order, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report released.']);

        return redirect()->route('reports.show', $report);
    }

    public function correct(Request $request, ReportVersion $report, ReportService $reports): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user = $request->user();
        abort_if($user === null, 403);
        $reports->beginCorrection($report, $user, $data['reason']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correction started. Update the results, then release the new version.']);

        return redirect()->route('orders.results', $report->order_id);
    }

    public function deliver(Request $request, ReportVersion $report, ReportService $reports): RedirectResponse
    {
        $data = $request->validate(['channel' => ['required', 'in:in_app,email']]);
        $user = $request->user();
        abort_if($user === null, 403);
        $reports->deliver($report, $user, $data['channel']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Delivery recorded.']);

        return back();
    }

    public function withdraw(Request $request, ReportVersion $report, ReportService $reports): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user = $request->user();
        abort_if($user === null, 403);
        $reports->withdraw($report, $user, $data['reason']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report withdrawn.']);

        return back();
    }

    public function download(Request $request, ReportVersion $report): StreamedResponse
    {
        abort_if($report->file_path === null || ! Storage::disk('local')->exists($report->file_path), 404);
        $report->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'downloaded',
        ]);

        return Storage::disk('local')->download($report->file_path, $report->report_number.'-v'.$report->version.'.pdf');
    }

    public function verify(string $token): Response
    {
        $report = ReportVersion::query()
            ->with('order.branch.lab')
            ->where('verification_token', $token)
            ->first();

        return Inertia::render('public/verify', [
            'authentic' => $report !== null && in_array($report->status, ['released', 'delivered', 'superseded'], true),
            'summary' => $report === null ? null : [
                'report_number' => $report->report_number,
                'version' => $report->version,
                'status' => $report->status,
                'released_at' => $report->released_at,
                'lab' => $report->order->branch->lab->name,
                'checksum_hint' => $report->checksum ? substr($report->checksum, 0, 10) : null,
            ],
        ]);
    }

    public function templates(): Response
    {
        return Inertia::render('lab/templates/index', [
            'templates' => ReportTemplate::query()->with('department:id,name', 'branch:id,name')->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'header_style' => ['required', 'in:branded_left,centered,compact'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);
        $user = $request->user();
        abort_if($user === null || $user->lab_id === null, 403);

        $template = ReportTemplate::query()->create([
            'lab_id' => $user->lab_id,
            'branch_id' => $user->branch_id,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'header_style' => $data['header_style'],
            'version' => 1,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        if ($template->department_id) {
            Department::query()->whereKey($template->department_id)->update(['report_template_id' => $template->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template approved.']);

        return back();
    }
}
