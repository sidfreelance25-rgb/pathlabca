<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lab;
use App\Models\Order;
use App\Models\Patient;
use App\Models\ReportVersion;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsappReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_connects_their_own_number_and_sends_the_report(): void
    {
        Storage::fake('local');
        $lab = Lab::query()->create(['name' => 'PathLabCA']);
        $branch = Branch::query()->create(['lab_id' => $lab->id, 'name' => 'Central', 'code' => 'BLR']);
        $technician = User::factory()->create([
            'role' => 'technician',
            'lab_id' => $lab->id,
            'branch_id' => $branch->id,
        ]);
        $patient = Patient::query()->create([
            'lab_id' => $lab->id,
            'patient_number' => 'PAT00009',
            'name' => 'Sid',
            'sex' => 'male',
            'phone' => '9812345678',
            'is_demo' => false,
        ]);
        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'ordered_by' => $technician->id,
            'order_number' => 'ORD0001',
            'priority' => 'routine',
            'status' => 'resulted',
            'ordered_at' => now(),
        ]);
        Storage::disk('local')->put('reports/RPT0001.pdf', '%PDF-1.4 report');
        ReportVersion::query()->create([
            'order_id' => $order->id,
            'report_number' => 'RPT0001',
            'version' => 1,
            'template_version' => 1,
            'status' => 'released',
            'snapshot' => ['status' => 'released'],
            'file_path' => 'reports/RPT0001.pdf',
            'verification_token' => 'token-rpt-0001',
            'released_by' => $technician->id,
            'released_at' => now(),
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['display_phone_number' => '+91 98000 00001', 'verified_name' => 'Lab Technician'])
                ->push(['id' => 'media-1'])
                ->push(['messages' => [['id' => 'wamid.1']]]),
        ]);

        $this->actingAs($technician)->put(route('whatsapp.update'), [
            'phone_number_id' => '106540352242922',
            'access_token' => 'secret-token',
            'template_name' => 'lab_report',
        ])->assertRedirect();

        $account = WhatsappAccount::query()->where('user_id', $technician->id)->firstOrFail();
        $this->assertSame('+91 98000 00001', $account->display_number);
        $this->assertSame('secret-token', $account->access_token);
        $this->assertSame('lab_report', $account->template_name);

        $this->actingAs($technician)->post(route('orders.whatsapp', $order))->assertRedirect();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/messages')
                && $request['to'] === '919812345678'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'lab_report';
        });
    }

    public function test_another_technician_cannot_use_a_number_they_did_not_connect(): void
    {
        $lab = Lab::query()->create(['name' => 'PathLabCA']);
        $branch = Branch::query()->create(['lab_id' => $lab->id, 'name' => 'Central', 'code' => 'BLR']);
        $other = User::factory()->create([
            'role' => 'technician',
            'lab_id' => $lab->id,
            'branch_id' => $branch->id,
        ]);
        $patient = Patient::query()->create([
            'lab_id' => $lab->id,
            'patient_number' => 'PAT00010',
            'name' => 'Sid',
            'sex' => 'male',
            'phone' => '9812345678',
        ]);
        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'order_number' => 'ORD0002',
            'status' => 'resulted',
            'ordered_at' => now(),
        ]);

        $this->actingAs($other)->post(route('orders.whatsapp', $order))
            ->assertSessionHasErrors('whatsapp');
    }
}
