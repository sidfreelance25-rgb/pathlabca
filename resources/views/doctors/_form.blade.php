@php($doctor = $doctor ?? null)

<label for="name">Doctor name</label>
<input id="name" type="text" name="name" value="{{ old('name', $doctor?->name) }}" required>

<label for="phone">Phone</label>
<input id="phone" type="text" name="phone" value="{{ old('phone', $doctor?->phone) }}">

<label for="specialization">Specialization</label>
<input id="specialization" type="text" name="specialization" value="{{ old('specialization', $doctor?->specialization) }}">

<input type="hidden" name="is_active" value="0">
<div class="checkbox">
    <input id="is_active" type="checkbox" name="is_active" value="1"
        @checked((string) old('is_active', $doctor?->is_active ?? true) === '1' || old('is_active', $doctor?->is_active ?? true) === true)>
    <label for="is_active">Active (available in report Refer by list)</label>
</div>
