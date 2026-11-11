@php($report = $report ?? null)
@php($selectedDoctorId = old('referred_by_doctor_id', $report?->referred_by_doctor_id))

<label for="patient_name">Patient name</label>
<input id="patient_name" type="text" name="patient_name" value="{{ old('patient_name', $report?->patient_name) }}" required>

<label for="referred_by_doctor_id">Refer by (doctor name)</label>
<select id="referred_by_doctor_id" name="referred_by_doctor_id">
    <option value="" @selected($selectedDoctorId === null || $selectedDoctorId === '')>Self</option>
    @foreach ($doctors as $doctor)
        <option value="{{ $doctor->id }}" @selected((string) $selectedDoctorId === (string) $doctor->id)>
            {{ $doctor->name }}
        </option>
    @endforeach
</select>
<p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem;font-size:0.9rem;">
    Defaults to <strong>Self</strong>. Manage your private doctor list in
    <a href="{{ route('doctors.index') }}">Doctors</a>.
</p>
