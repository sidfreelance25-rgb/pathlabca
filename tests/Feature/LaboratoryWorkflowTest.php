<?php

namespace Tests\Feature;

use App\Models\CriticalAlert;
use App\Models\LetterheadTemplate;
use App\Models\Patient;
use App\Models\ReportVersion;
use App\Models\Result;
use App\Models\Specimen;
use App\Models\TestDefinition;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\ResultService;
use App\Services\SpecimenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LaboratoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_a_technician_can_open_a_new_report(): void
    {
        $technician = User::query()->where('email', 'tech@pathlabca.test')->firstOrFail();

        $this->actingAs($technician)->get(route('orders.create'))->assertOk();
    }

    public function test_catalogue_covers_the_major_laboratory_types(): void
    {
        $codes = TestDefinition::query()->pluck('code');

        foreach ([
            'CBC', 'PTINR', 'LFT', 'KFT', 'LIPID', 'THYROID', 'HORMONE',
            'HIV', 'DENGUE', 'URINE', 'UCULT', 'HISTO', 'PAP', 'FLUIDCYT',
            'BLOODGROUP', 'XMATCH', 'COVIDPCR', 'MTBPCR', 'HBVDNA',
            'SEMEN', 'CSF', 'AFP', 'PSA', 'TROP', 'BNP',
        ] as $code) {
            $this->assertTrue($codes->contains($code), $code.' is missing from the catalogue.');
        }

        $this->assertGreaterThanOrEqual(150, $codes->count());
    }

    public function test_price_and_letterhead_are_specific_to_the_user(): void
    {
        $admin = User::query()->where('email', 'admin@pathlabca.test')->firstOrFail();
        $technician = User::query()->where('email', 'tech@pathlabca.test')->firstOrFail();
        $cbc = TestDefinition::query()->where('code', 'CBC')->whereNull('owner_user_id')->firstOrFail();
        $sharedPrice = $cbc->price_paise;
        $shared = LetterheadTemplate::query()->whereNull('owner_user_id')->where('is_default', true)->firstOrFail();

        $this->actingAs($technician)->patch(route('catalogue.update', $cbc), [
            'name' => 'My CBC',
            'price_rupees' => 111,
            'active' => true,
        ])->assertRedirect();

        $cbc->refresh();
        $this->assertSame($sharedPrice, $cbc->price_paise);
        $copy = TestDefinition::query()->where('owner_user_id', $technician->id)->where('source_test_id', $cbc->id)->firstOrFail();
        $this->assertSame('My CBC', $copy->name);
        $this->assertSame(11100, $copy->price_paise);
        $this->assertSame(14, $copy->components()->count());
        $this->assertSame(1, TestDefinition::query()->where('owner_user_id', $technician->id)->where('source_test_id', $cbc->id)->count());

        $this->actingAs($technician)->patch(route('catalogue.update', $cbc), [
            'name' => 'My CBC',
            'price_rupees' => 112,
            'active' => true,
        ])->assertRedirect();
        $this->assertSame(11200, $copy->refresh()->price_paise);
        $this->assertSame(1, TestDefinition::query()->where('owner_user_id', $technician->id)->where('source_test_id', $cbc->id)->count());

        $patient = Patient::query()->create([
            'lab_id' => $technician->lab_id,
            'patient_number' => 'PAT88001',
            'name' => 'Copy Patient',
            'sex' => 'female',
            'date_of_birth' => '1992-02-02',
            'is_demo' => false,
        ]);
        $this->assertNotNull($technician->branch);
        $order = app(OrderService::class)->place($technician, $patient, $technician->branch, [$cbc->id], 'routine', null, null);
        $this->assertSame($copy->id, $order->items->first()?->test_definition_id);
        $this->assertSame(11200, $order->items->first()?->price_paise);

        $this->actingAs($admin)->patch(route('catalogue.update', $cbc), [
            'name' => 'CBC',
            'price_rupees' => 222,
            'active' => true,
        ])->assertRedirect();
        $this->assertSame(22200, $cbc->refresh()->price_paise);
        $this->assertSame(11200, $copy->refresh()->price_paise);

        $this->actingAs($technician)->post(route('catalogue.promote', $copy))->assertForbidden();
        $this->actingAs($admin)->post(route('catalogue.promote', $copy))->assertRedirect();
        $this->assertSame(11200, $cbc->refresh()->price_paise);
        $this->assertNotNull(TestDefinition::query()->find($copy->id));

        $plain = TestDefinition::query()->whereNull('owner_user_id')->where('is_panel', false)->firstOrFail();
        $this->actingAs($technician)->patch(route('catalogue.update', $plain), [
            'name' => 'Bench copy',
            'price_rupees' => 15,
            'active' => true,
        ])->assertRedirect();
        $plainCopy = TestDefinition::query()->where('owner_user_id', $technician->id)->where('source_test_id', $plain->id)->firstOrFail();
        $this->actingAs($admin)->post(route('catalogue.promote', $plainCopy))->assertRedirect();
        $this->assertSame(1500, $plain->refresh()->price_paise);
        $this->assertSame('Bench copy', $plain->name);
        $this->assertNull(TestDefinition::query()->find($plainCopy->id));

        $this->actingAs($technician)->post(route('catalogue.store'), [
            'name' => 'Desk smear',
            'code' => 'DESKSMEAR',
            'department_id' => $cbc->department_id,
            'specimen_type_id' => $cbc->specimen_type_id,
            'price_rupees' => 80,
            'unit' => 'qual',
        ])->assertRedirect();
        $personal = TestDefinition::query()->where('code', 'DESKSMEAR')->firstOrFail();
        $this->assertSame($technician->id, $personal->owner_user_id);

        $this->actingAs($admin)->post(route('catalogue.store'), [
            'name' => 'Admin panel',
            'code' => 'ADMINTEST',
            'department_id' => $cbc->department_id,
            'specimen_type_id' => $cbc->specimen_type_id,
            'price_rupees' => 50,
        ])->assertRedirect();
        $this->assertNull(TestDefinition::query()->where('code', 'ADMINTEST')->value('owner_user_id'));

        $this->actingAs($admin)->post(route('catalogue.promote', $personal))->assertRedirect();
        $this->assertNull($personal->refresh()->owner_user_id);

        $this->actingAs($technician)->put(route('letterhead.update'), $this->letterheadPayload($shared->id, 'City Pathology', 'save'))->assertRedirect();
        $shared->refresh();
        $this->assertSame('PathLabCA', $shared->design['lab']['name'] ?? null);
        $mine = LetterheadTemplate::query()->where('owner_user_id', $technician->id)->firstOrFail();
        $this->assertSame($shared->id, $mine->source_letterhead_id);
        $this->assertSame('City Pathology', $mine->design['lab']['name'] ?? null);
        $this->assertSame($mine->id, $technician->refresh()->active_letterhead_id);
        $this->assertNull(LetterheadTemplate::query()->where('owner_user_id', $admin->id)->first());

        $this->actingAs($admin)->put(route('letterhead.update'), $this->letterheadPayload($shared->id, 'Central Laboratory', 'save'))->assertRedirect();
        $this->assertSame('Central Laboratory', $shared->refresh()->design['lab']['name'] ?? null);
        $this->assertSame('City Pathology', $mine->refresh()->design['lab']['name'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function letterheadPayload(int $id, string $labName, string $intent): array
    {
        return [
            'letterhead_id' => $id,
            'name' => 'Bench letterhead',
            'intent' => $intent,
            'make_default' => false,
            'theme' => 't3',
            'colors' => ['t3' => ['c1' => '#ff7a00', 'c2' => '#e2b53a', 'c3' => '#002d6a']],
            'hide' => [],
            'lab' => [
                'name' => $labName,
                'tagline' => 'Accurate | Caring | Instant',
                'address' => '12 Clinic Road',
                'phone' => '9800000000',
                'email' => 'city@pathlabca.test',
                'iso' => 'NABL demo',
                'footerNote' => 'Open daily',
                'techName' => 'Ravi Patel',
                'techRole' => 'Technician',
                'doctor' => 'Dr. Vimal Shah',
                'qual' => 'MD Pathology',
            ],
        ];
    }

    public function test_cbc_order_creates_a_barcode_and_component_results(): void
    {
        $admin = User::query()->where('email', 'admin@pathlabca.test')->firstOrFail();
        $patient = Patient::query()->where('is_demo', false)->first();

        if ($patient === null) {
            $patient = Patient::query()->create([
                'lab_id' => $admin->lab_id,
                'patient_number' => 'PAT99999',
                'name' => 'Real Fixture',
                'sex' => 'female',
                'date_of_birth' => '1988-01-01',
                'is_demo' => false,
            ]);
        }

        $cbc = TestDefinition::query()->where('code', 'CBC')->firstOrFail();
        $order = app(OrderService::class)->place($admin, $patient, $admin->branch, [$cbc->id], 'urgent', null, null);

        $this->assertCount(1, $order->specimens);
        $this->assertSame(14, $cbc->components()->count());
        $this->assertNotSame('', $order->specimens->first()->barcode);
        $this->assertSame(
            1,
            Specimen::query()->where('barcode', $order->specimens->first()->barcode)->count(),
        );
    }

    public function test_critical_result_blocks_release_until_acknowledged_and_snapshot_is_immutable(): void
    {
        $admin = User::query()->where('email', 'admin@pathlabca.test')->firstOrFail();
        $reception = User::query()->where('email', 'reception@pathlabca.test')->firstOrFail();
        $patient = Patient::query()->create([
            'lab_id' => $admin->lab_id,
            'patient_number' => 'PAT88888',
            'name' => 'Critical Fixture',
            'sex' => 'male',
            'date_of_birth' => '1975-06-01',
            'is_demo' => false,
        ]);
        $potassium = TestDefinition::query()->where('code', 'K')->firstOrFail();
        $order = app(OrderService::class)->place($admin, $patient, $admin->branch, [$potassium->id], 'urgent', null, null);
        $specimens = app(SpecimenService::class);

        foreach ($order->specimens as $specimen) {
            $specimens->collect($specimen, $admin, 'Arm', 'Acceptable');
            $specimens->receive($specimen->refresh(), $admin, 'Acceptable');
        }

        $definition = $potassium->resultDefinitions()->firstOrFail();
        $results = app(ResultService::class);
        $results->save($order, $admin, [
            $definition->id => ['value' => '7.0', 'comment' => 'Repeat confirmed'],
        ]);

        $result = Result::query()->where('result_definition_id', $definition->id)->firstOrFail();
        $this->assertSame('CH', $result->flag);
        $this->assertNotNull($result->reference_snapshot);
        $this->assertSame('ABIM Laboratory Test Reference Ranges, January 2026', $result->reference_snapshot['source']);

        $results->reviewTechnically($order->refresh(), $admin);

        try {
            $results->validateClinically($order->refresh(), $admin);
            $this->fail('Open critical results must block validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('results', $exception->errors());
        }

        $order->refresh();
        $this->assertNotSame('validated', $order->status);

        $alert = CriticalAlert::query()->where('result_id', $result->id)->firstOrFail();
        $results->acknowledgeCritical($alert, $admin, 'Clinician informed at 10:15.');
        $results->validateClinically($order->refresh(), $admin);

        $this->actingAs($reception)
            ->post(route('reports.release', $order))
            ->assertForbidden();

        $report = app(ReportService::class)->release($order->refresh(), $admin);
        $checksum = $report->checksum;
        $this->assertSame('Potassium', $report->snapshot['sections']['Biochemistry'][0]['name']);

        $potassium->update(['name' => 'Potassium renamed after release']);
        $report->refresh();
        $this->assertSame($checksum, $report->checksum);
        $this->assertSame('Potassium', $report->snapshot['sections']['Biochemistry'][0]['name']);

        $this->get(route('reports.verify', $report->verification_token))
            ->assertOk()
            ->assertSee('"authentic":true', false)
            ->assertSee($report->report_number, false)
            ->assertDontSee('Critical Fixture', false);

        app(ReportService::class)->beginCorrection($report, $admin, 'Value transcribed from the wrong run.');
        $results->reviewTechnically($order->refresh(), $admin);
        $results->validateClinically($order->refresh(), $admin);
        $replacement = app(ReportService::class)->release($order->refresh(), $admin);

        $this->assertSame(2, $replacement->version);
        $this->assertSame('superseded', $report->refresh()->status);
        $this->assertNotSame($report->file_path, $replacement->file_path);
        $this->assertNotNull($replacement->checksum);

        $demo = ReportVersion::query()->where('status', 'released')->where('version', 1)->orderBy('id')->firstOrFail();
        $this->assertTrue($demo->snapshot['is_demo']);

        try {
            app(ReportService::class)->deliver($demo, $admin, 'email');
            $this->fail('Demonstration reports must not be delivered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delivery', $exception->errors());
        }
    }
}
