<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_defaults_referred_by_to_self(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reports.store'), [
            'patient_name' => 'Asha Patel',
            'referred_by_doctor_id' => null,
        ]);

        $report = Report::query()->first();

        $response->assertRedirect(route('reports.show', $report));
        $this->assertSame(Report::REFERRED_BY_SELF, $report->referred_by_name);
        $this->assertNull($report->referred_by_doctor_id);
    }

    public function test_report_can_use_doctor_from_current_user_list(): void
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'name' => 'Dr. Mehta',
        ]);

        $this->actingAs($user)->post(route('reports.store'), [
            'patient_name' => 'Ravi Kumar',
            'referred_by_doctor_id' => $doctor->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'patient_name' => 'Ravi Kumar',
            'referred_by_doctor_id' => $doctor->id,
            'referred_by_name' => 'Dr. Mehta',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_refer_using_another_users_doctor(): void
    {
        $user = User::factory()->create();
        $otherDoctor = Doctor::factory()->create([
            'name' => 'Dr. Other',
        ]);

        $this->actingAs($user)->post(route('reports.store'), [
            'patient_name' => 'Someone',
            'referred_by_doctor_id' => $otherDoctor->id,
        ])->assertSessionHasErrors('referred_by_doctor_id');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_doctors_are_scoped_per_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Doctor::factory()->create([
            'user_id' => $user->id,
            'name' => 'Mine',
        ]);
        Doctor::factory()->create([
            'user_id' => $other->id,
            'name' => 'Theirs',
        ]);

        $this->actingAs($user)
            ->get(route('doctors.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_user_can_add_doctor_to_their_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('doctors.store'), [
            'name' => 'Dr. Kapoor',
            'phone' => '9999999999',
            'specialization' => 'Cardiologist',
            'is_active' => '1',
        ])->assertRedirect(route('doctors.index'));

        $this->assertDatabaseHas('doctors', [
            'user_id' => $user->id,
            'name' => 'Dr. Kapoor',
            'is_active' => 1,
        ]);
    }
}
