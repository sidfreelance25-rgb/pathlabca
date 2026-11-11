<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\Order;
use App\Models\ReportVersion;
use App\Models\Specimen;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'orders' => Order::query()->count(),
                'collection' => Specimen::query()->where('status', 'pending')->count(),
                'accession' => Specimen::query()->where('status', 'collected')->count(),
                'validation' => Order::query()->where('status', 'resulted')->count(),
                'reports' => ReportVersion::query()->where('status', 'released')->count(),
                'critical' => CriticalAlert::query()->where('status', 'open')->count(),
            ],
        ]);
    }
}
