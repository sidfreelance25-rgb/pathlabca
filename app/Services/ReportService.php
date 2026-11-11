<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReportTemplate;
use App\Models\ReportVersion;
use App\Models\Result;
use App\Models\User;
use App\Support\Letterhead;
use App\Support\LetterheadDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(
        private AuditRecorder $audit,
        private BarcodeService $barcodes,
        private ResultService $results,
    ) {}

    public function preview(Order $order, User $actor): ReportVersion
    {
        return $this->render($order, $actor, 'draft');
    }

    public function release(Order $order, User $actor): ReportVersion
    {
        $this->guardReleasable($order);
        $report = $this->render($order, $actor, 'released');
        $order->update(['status' => 'reported']);
        $order->items()->update(['status' => 'released']);

        ReportVersion::query()
            ->where('order_id', $order->id)
            ->where('id', '!=', $report->id)
            ->where('status', 'released')
            ->update(['status' => 'superseded']);

        $this->audit->record($actor, 'report.released', $report);

        return $report;
    }

    public function beginCorrection(ReportVersion $current, User $actor, string $reason): ReportVersion
    {
        if ($current->status !== 'released') {
            throw ValidationException::withMessages([
                'reason' => 'Only a released report can be corrected.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A correction needs a reason.',
            ]);
        }

        Result::query()
            ->whereIn('order_item_id', $current->order->items()->pluck('id'))
            ->update(['status' => 'entered']);
        $current->order->update(['status' => 'resulted']);

        $draft = ReportVersion::query()->create([
            'order_id' => $current->order_id,
            'report_template_id' => $current->report_template_id,
            'report_number' => $current->report_number,
            'version' => $current->version + 1,
            'template_version' => $current->template_version,
            'status' => 'draft',
            'snapshot' => ['pending_correction' => true],
            'verification_token' => $current->verification_token.'-v'.($current->version + 1),
            'correction_reason' => $reason,
            'supersedes_id' => $current->id,
        ]);

        $this->audit->record($actor, 'report.correction_started', $draft, $reason);

        return $draft;
    }

    public function deliver(ReportVersion $report, User $actor, string $channel): void
    {
        if ($report->status !== 'released') {
            throw ValidationException::withMessages([
                'delivery' => 'Only a released report can be delivered.',
            ]);
        }

        $snapshot = $report->snapshot;

        if (($snapshot['is_demo'] ?? false) === true) {
            throw ValidationException::withMessages([
                'delivery' => 'Demonstration reports cannot be delivered as clinical documents.',
            ]);
        }

        $report->update(['status' => 'delivered']);
        $report->events()->create([
            'user_id' => $actor->id,
            'event' => 'delivered',
            'meta' => ['channel' => $channel],
        ]);
        $this->audit->record($actor, 'report.delivered', $report, null, ['channel' => $channel]);
    }

    public function withdraw(ReportVersion $report, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Withdrawal needs a reason.',
            ]);
        }

        $report->update(['status' => 'withdrawn']);
        $report->events()->create([
            'user_id' => $actor->id,
            'event' => 'withdrawn',
            'meta' => ['reason' => $reason],
        ]);
        $this->audit->record($actor, 'report.withdrawn', $report, $reason);
    }

    private function render(Order $order, User $actor, string $status): ReportVersion
    {
        return DB::transaction(function () use ($order, $actor, $status): ReportVersion {
            $order->load([
                'patient',
                'doctor',
                'branch.lab',
                'branch.reportTemplate',
                'specimens.specimenType',
                'items.test.department.reportTemplate',
                'items.test.specimenType',
                'items.test.resultDefinitions',
                'items.test.components.resultDefinitions',
                'items.results.definition',
                'reports',
            ]);

            $template = $this->templateFor($order);
            $draft = $order->reports->first(fn (ReportVersion $report): bool => $report->status === 'draft');
            $released = $order->reports->first(fn (ReportVersion $report): bool => $report->status === 'released');

            if ($draft === null && $released !== null) {
                throw ValidationException::withMessages([
                    'report' => 'This report is already released. Start a correction to issue a new version.',
                ]);
            }

            $report = $draft ?? new ReportVersion([
                'order_id' => $order->id,
                'report_number' => $this->nextReportNumber(),
                'version' => 1,
                'verification_token' => bin2hex(random_bytes(16)),
            ]);

            $snapshot = $this->snapshot($order, $template, $actor, $report->verification_token, $status, $report->correction_reason);
            $binary = $this->pdfBinary($snapshot);
            $path = 'reports/'.$report->report_number.'-v'.$report->version.'-'.$status.'.pdf';

            if ($report->file_path && $report->status === 'draft') {
                Storage::disk('local')->delete($report->file_path);
            }

            Storage::disk('local')->put($path, $binary);

            $report->fill([
                'report_template_id' => $template->id,
                'template_version' => $template->version,
                'status' => $status,
                'snapshot' => $snapshot,
                'file_path' => $path,
                'checksum' => hash('sha256', $binary),
                'released_by' => $status === 'released' ? $actor->id : null,
                'released_at' => $status === 'released' ? now() : null,
            ])->save();

            $report->events()->create([
                'user_id' => $actor->id,
                'event' => $status === 'released' ? 'released' : 'previewed',
            ]);
            $this->audit->record($actor, $status === 'released' ? 'report.generated' : 'report.previewed', $report);

            return $report->refresh();
        });
    }

    private function guardReleasable(Order $order): void
    {
        $order->load(['items.results', 'items.test.resultDefinitions', 'items.test.components.resultDefinitions']);

        foreach ($order->items as $item) {
            $results = $item->results->keyBy('result_definition_id');

            foreach ($this->results->definitionsFor($item->test) as $definition) {
                $result = $results->get($definition->id);

                if ($definition->formula !== null && ($result === null || $result->value === null)) {
                    continue;
                }

                if ($result === null || $result->status !== 'validated') {
                    throw ValidationException::withMessages([
                        'report' => 'Release is blocked until every result is clinically validated.',
                    ]);
                }
            }
        }

        $openCritical = $order->items
            ->flatMap(fn (OrderItem $item) => $item->results)
            ->contains(fn (Result $result): bool => $result->criticalAlert()->where('status', 'open')->exists());

        if ($openCritical) {
            throw ValidationException::withMessages([
                'report' => 'Acknowledge critical results before release.',
            ]);
        }
    }

    private function templateFor(Order $order): ReportTemplate
    {
        $departmentTemplate = $order->items
            ->map(fn (OrderItem $item): ?ReportTemplate => $item->test->department->reportTemplate)
            ->filter(fn (?ReportTemplate $template): bool => $template?->status === 'approved')
            ->first();

        if ($departmentTemplate instanceof ReportTemplate) {
            return $departmentTemplate;
        }

        $branchTemplate = $order->branch->reportTemplate;

        if ($branchTemplate?->status === 'approved') {
            return $branchTemplate;
        }

        $fallback = ReportTemplate::query()
            ->where('lab_id', $order->branch->lab_id)
            ->where('status', 'approved')
            ->orderBy('id')
            ->first();

        if ($fallback === null) {
            throw ValidationException::withMessages([
                'report' => 'Approve a report template before generating a PDF.',
            ]);
        }

        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Order $order, ReportTemplate $template, User $actor, string $token, string $status, ?string $reason): array
    {
        $patient = $order->patient;
        $sections = [];

        foreach ($order->items as $item) {
            $section = $item->test->report_section;
            $rows = [];

            foreach ($this->results->definitionsFor($item->test) as $definition) {
                $result = $item->results->firstWhere('result_definition_id', $definition->id);

                if ($result === null) {
                    continue;
                }

                $rows[] = [
                    'label' => $definition->label,
                    'value' => $result->value,
                    'unit' => $result->unit,
                    'interval' => $result->reference_snapshot['display'] ?? '',
                    'flag' => $result->flag,
                    'comment' => $result->comment,
                    'source' => $result->reference_snapshot['source'] ?? null,
                ];
            }

            $sections[$section][] = [
                'name' => $item->test->name,
                'method' => $item->test->method,
                'specimen' => $item->test->specimenType->name,
                'rows' => $rows,
            ];
        }

        return [
            'status' => $status,
            'is_demo' => (bool) $patient->is_demo,
            'correction_reason' => $reason,
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'header_style' => $template->header_style,
                'version' => $template->version,
            ],
            'lab' => [
                'name' => $order->branch->lab->name,
                'legal_name' => $order->branch->lab->legal_name,
                'phone' => $order->branch->lab->phone,
                'email' => $order->branch->lab->email,
                'website' => $order->branch->lab->website,
                'licence_number' => $order->branch->lab->licence_number,
                'address' => $order->branch->lab->address,
            ],
            'branch' => [
                'name' => $order->branch->name,
                'code' => $order->branch->code,
                'address' => $order->branch->address,
                'phone' => $order->branch->phone,
            ],
            'patient' => [
                'number' => $patient->patient_number,
                'name' => $patient->name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth?->toDateString(),
                'age' => $patient->ageYears(),
            ],
            'order' => [
                'number' => $order->order_number,
                'priority' => $order->priority,
                'ordered_at' => $order->ordered_at?->toIso8601String(),
                'clinical_notes' => $order->clinical_notes,
            ],
            'doctor' => $order->doctor === null ? null : [
                'name' => $order->doctor->name,
                'qualification' => $order->doctor->qualification,
            ],
            'specimens' => $order->specimens->map(fn ($specimen): array => [
                'barcode' => $specimen->barcode,
                'barcode_image' => $this->barcodes->code128DataUri($specimen->barcode),
                'type' => $specimen->specimenType->name,
                'collected_at' => $specimen->collected_at?->toIso8601String(),
                'received_at' => $specimen->received_at?->toIso8601String(),
                'condition' => $specimen->condition,
            ])->all(),
            'sections' => $sections,
            'signatory' => [
                'name' => $actor->name,
                'qualification' => $actor->qualification,
                'designation' => $actor->designation,
                'signed_at' => now()->toIso8601String(),
            ],
            'verification_url' => route('reports.verify', $token),
            'qr' => $this->barcodes->qrDataUri(route('reports.verify', $token)),
            'generated_at' => now()->toIso8601String(),
            'letterhead' => Letterhead::forUser($actor),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function pdfBinary(array $snapshot): string
    {
        $rows = [];
        $title = 'Laboratory report';
        $sections = is_array($snapshot['sections'] ?? null) ? $snapshot['sections'] : [];

        foreach ($sections as $panels) {
            if (! is_array($panels)) {
                continue;
            }

            foreach ($panels as $panel) {
                if (! is_array($panel)) {
                    continue;
                }

                $title = (string) ($panel['name'] ?? $title);
                foreach ($panel['rows'] ?? [] as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
            }
        }

        $patient = is_array($snapshot['patient'] ?? null) ? $snapshot['patient'] : [];
        $specimens = is_array($snapshot['specimens'] ?? null) ? $snapshot['specimens'] : [];
        $firstSpecimen = is_array($specimens[0] ?? null) ? $specimens[0] : [];
        $doctor = is_array($snapshot['doctor'] ?? null) ? $snapshot['doctor'] : [];
        $design = is_array($snapshot['letterhead'] ?? null) ? $snapshot['letterhead'] : Letterhead::defaults();
        $document = app(LetterheadDocument::class);
        $html = $document->html($design, [
            'title' => $title,
            'patient' => (string) ($patient['name'] ?? ''),
            'agesex' => (string) (($patient['age'] ?? '—').' / '.($patient['sex'] ?? '')),
            'sample_id' => (string) ($firstSpecimen['barcode'] ?? ($snapshot['order']['number'] ?? 'SAMPLE')),
            'refBy' => (string) ($doctor['name'] ?? '—'),
            'repDate' => now()->format('d M Y'),
            'collected' => (string) ($firstSpecimen['collected_at'] ?? '—'),
            'rows' => $rows,
            'qr' => (string) ($snapshot['verification_url'] ?? ''),
            'note' => '',
            'draft' => ! in_array($snapshot['status'] ?? '', ['released', 'delivered'], true),
            'demo' => (bool) ($snapshot['is_demo'] ?? false),
        ]);
        $chrome = $document->pdf($html);

        if (is_string($chrome)) {
            return $chrome;
        }

        return Pdf::loadView('reports.clinical', [
            'report' => $snapshot,
            'headerStyle' => 'branded_left',
        ])->setPaper('a4')->setOption('isPhpEnabled', true)->output();
    }

    private function nextReportNumber(): string
    {
        $root = 'RPT'.now()->format('ymd');
        $latest = ReportVersion::query()
            ->where('report_number', 'like', $root.'%')
            ->lockForUpdate()
            ->orderByDesc('report_number')
            ->value('report_number');
        $sequence = 1;

        if (is_string($latest) && str_starts_with($latest, $root)) {
            $sequence = ((int) substr($latest, strlen($root))) + 1;
        }

        return $root.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
