<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Patient;
use App\Models\ReportVersion;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $recent = ReportVersion::query()
            ->with(['order.patient:id,name', 'order.items.test:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (ReportVersion $report): array {
                $tests = $report->order?->items
                    ->map(fn ($item): string => (string) ($item->test->name ?? ''))
                    ->filter()
                    ->implode(', ');

                return [
                    'id' => $report->id,
                    'patient' => $report->order?->patient->name ?? 'Unknown patient',
                    'tests' => $tests !== '' ? $tests : 'Laboratory report',
                    'status' => $report->status,
                    'date' => $report->created_at?->toDateString(),
                ];
            });

        return Inertia::render('dashboard', [
            'stats' => [
                'generated_today' => ReportVersion::query()->whereDate('created_at', Carbon::today())->count(),
                'registered' => Patient::query()->count(),
                'awaiting' => Order::query()->whereNotIn('status', ['reported', 'cancelled'])->count(),
                'released' => ReportVersion::query()->where('status', 'released')->count(),
            ],
            'recent' => $recent,
        ]);
    }
}
