<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Doctor;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Report::class);

        $reports = $request->user()
            ->reports()
            ->latest()
            ->paginate(15);

        return view('reports.index', ['reports' => $reports]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Report::class);

        return view('reports.create', [
            'doctors' => $this->activeDoctorsFor($request->user()),
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $referral = $this->resolveReferral(
            $request->user(),
            $request->validated('referred_by_doctor_id'),
        );

        $report = $request->user()->reports()->create([
            'patient_name' => $request->validated('patient_name'),
            ...$referral,
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('status', 'Report created.');
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        return view('reports.show', ['report' => $report]);
    }

    public function edit(Request $request, Report $report): View
    {
        $this->authorize('update', $report);

        return view('reports.edit', [
            'report' => $report,
            'doctors' => $this->activeDoctorsFor($request->user()),
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $referral = $this->resolveReferral(
            $request->user(),
            $request->validated('referred_by_doctor_id'),
        );

        $report->update([
            'patient_name' => $request->validated('patient_name'),
            ...$referral,
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('status', 'Report updated.');
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return redirect()
            ->route('reports.index')
            ->with('status', 'Report deleted.');
    }

    /**
     * @return array{referred_by_doctor_id: int|null, referred_by_name: string}
     */
    private function resolveReferral(User $user, mixed $doctorId): array
    {
        if ($doctorId === null || $doctorId === '' || $doctorId === 'self') {
            return [
                'referred_by_doctor_id' => null,
                'referred_by_name' => Report::REFERRED_BY_SELF,
            ];
        }

        $doctor = $user->doctors()->active()->findOrFail((int) $doctorId);

        return [
            'referred_by_doctor_id' => $doctor->id,
            'referred_by_name' => $doctor->name,
        ];
    }

    /**
     * @return Collection<int, Doctor>
     */
    private function activeDoctorsFor(User $user)
    {
        return $user->doctors()->active()->orderBy('name')->get();
    }
}
