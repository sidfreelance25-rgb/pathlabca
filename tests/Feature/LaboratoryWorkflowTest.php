<?php

namespace Tests\Feature;

use App\Models\CriticalAlert;
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
            ->assertSee('Authentic released report')
            ->assertSee($report->report_number)
            ->assertDontSee('7.0')
            ->assertDontSee('Critical Fixture');

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
