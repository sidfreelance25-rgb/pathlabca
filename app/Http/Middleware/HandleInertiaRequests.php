<?php

namespace App\Http\Middleware;

use App\Support\LabAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'labNav' => $this->navigation($request),
        ];
    }

    /**
     * @return list<array{title: string, href: string, icon: string}>
     */
    private function navigation(Request $request): array
    {
        $user = $request->user();
        $items = [
            ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'dashboard', 'permission' => null],
            ['title' => 'Patients', 'href' => '/patients', 'icon' => 'patients', 'permission' => 'patient.read'],
            ['title' => 'Catalogue', 'href' => '/catalogue', 'icon' => 'catalogue', 'permission' => 'catalogue.read'],
            ['title' => 'Orders', 'href' => '/orders', 'icon' => 'orders', 'permission' => 'order.read'],
            ['title' => 'Collection', 'href' => '/collection', 'icon' => 'collection', 'permission' => 'specimen.read'],
            ['title' => 'Accession', 'href' => '/accession', 'icon' => 'accession', 'permission' => 'specimen.receive'],
            ['title' => 'Reports', 'href' => '/reports', 'icon' => 'reports', 'permission' => 'report.read'],
            ['title' => 'Critical', 'href' => '/critical', 'icon' => 'critical', 'permission' => 'result.read'],
            ['title' => 'Templates', 'href' => '/templates', 'icon' => 'templates', 'permission' => 'template.manage'],
        ];

        return array_values(array_map(
            fn (array $item): array => [
                'title' => $item['title'],
                'href' => $item['href'],
                'icon' => $item['icon'],
            ],
            array_filter($items, function (array $item) use ($user): bool {
                return $item['permission'] === null || LabAccess::allows($user, $item['permission']);
            }),
        ));
    }
}
