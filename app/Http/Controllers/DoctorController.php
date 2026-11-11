<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Doctor::class);

        $doctors = $request->user()
            ->doctors()
            ->latest()
            ->paginate(15);

        return view('doctors.index', ['doctors' => $doctors]);
    }

    public function create(): View
    {
        $this->authorize('create', Doctor::class);

        return view('doctors.create');
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $request->user()->doctors()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('doctors.index')
            ->with('status', 'Doctor added to your list.');
    }

    public function edit(Doctor $doctor): View
    {
        $this->authorize('update', $doctor);

        return view('doctors.edit', ['doctor' => $doctor]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update([
            ...$request->safe()->only(['name', 'phone', 'specialization']),
            'is_active' => $request->boolean('is_active', $doctor->is_active),
        ]);

        return redirect()
            ->route('doctors.index')
            ->with('status', 'Doctor updated.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $this->authorize('delete', $doctor);

        $doctor->delete();

        return redirect()
            ->route('doctors.index')
            ->with('status', 'Doctor removed from your list.');
    }
}
