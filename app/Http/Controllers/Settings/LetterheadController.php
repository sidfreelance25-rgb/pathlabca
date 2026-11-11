<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LetterheadTemplate;
use App\Services\LetterheadLibrary;
use App\Support\Letterhead;
use App\Support\LetterheadDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class LetterheadController extends Controller
{
    public function edit(Request $request, LetterheadLibrary $library): \Inertia\Response
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $requested = $request->integer('letterhead');
        $selected = $library->findVisible($user, $requested > 0 ? $requested : null) ?? $library->active($user);
        $activeId = $user->active_letterhead_id;
        $design = Letterhead::defaults($user);
        $selectedId = null;
        $selectedName = 'My letterhead';
        $selectedScope = 'personal';
        $selectedDefault = false;
        $selectedActive = false;

        if ($selected instanceof LetterheadTemplate) {
            $design = Letterhead::normalize($selected->design, $user);
            $selectedId = $selected->id;
            $selectedName = $selected->name;
            $selectedScope = $selected->owner_user_id === null ? 'global' : 'personal';
            $selectedDefault = $selected->is_default;
            $selectedActive = $selected->id === $activeId;
        }

        return Inertia::render('settings/letterhead', [
            'templates' => $library->visible($user)->map(fn (LetterheadTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'scope' => $template->owner_user_id === null ? 'global' : 'personal',
                'is_default' => $template->is_default,
                'active' => $template->id === $activeId,
                'theme' => is_string($template->design['theme'] ?? null) ? $template->design['theme'] : 't1',
            ])->values(),
            'selectedId' => $selectedId,
            'name' => $selectedName,
            'scope' => $selectedScope,
            'isDefault' => $selectedDefault,
            'active' => $selectedActive,
            'canManageMain' => $user->isLabAdmin(),
            'design' => $design,
            'missing' => Letterhead::missing($design),
        ]);
    }

    public function update(Request $request, LetterheadLibrary $library): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $data = $request->validate([
            'letterhead_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'intent' => ['required', 'in:save,copy,new_global'],
            'make_default' => ['sometimes', 'boolean'],
        ]);
        $design = $this->design($request);
        $letterheadId = $data['letterhead_id'] ?? null;
        $saved = $library->save(
            $user,
            is_numeric($letterheadId) ? (int) $letterheadId : null,
            $design,
            $data['name'],
            $data['intent'],
            (bool) ($data['make_default'] ?? false),
        );
        $message = match (true) {
            $data['intent'] === 'new_global' => 'Added a shared letterhead for everyone.',
            $saved->owner_user_id === null => 'Updated the shared letterhead for everyone who has not saved their own.',
            $data['intent'] === 'copy' || ($saved->source_letterhead_id !== null && $saved->wasRecentlyCreated) => 'Saved your copy. The shared letterhead is unchanged.',
            default => 'Saved your letterhead.',
        };
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('letterhead.edit', ['letterhead' => $saved->id]);
    }

    public function activate(Request $request, LetterheadTemplate $letterheadTemplate, LetterheadLibrary $library): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $library->activate($user, $letterheadTemplate);
        Inertia::flash('toast', ['type' => 'success', 'message' => $letterheadTemplate->owner_user_id === null
            ? 'Your reports now use this shared letterhead.'
            : 'Your reports now use your letterhead.']);

        return redirect()->route('letterhead.edit', ['letterhead' => $letterheadTemplate->id]);
    }

    public function preview(Request $request, LetterheadDocument $document): Response
    {
        $design = $this->design($request);
        $html = $document->html($design, [
            'title' => 'Complete Blood Count (CBC)',
            'patient' => 'Mr. Rahul Sharma',
            'agesex' => '34 Y / Male',
            'sample_id' => 'SP-2026-004187',
            'refBy' => 'Dr. A. Mehta',
            'repDate' => now()->format('d M Y'),
            'collected' => now()->format('d M Y, H:i'),
            'qr' => 'SP-2026-004187',
            'draft' => false,
            'demo' => true,
            'note' => 'Preview only. Reports use the letterhead you have selected.',
            'rows' => [
                ['label' => 'Haemoglobin', 'value' => '13.8', 'unit' => 'g/dL', 'interval' => '13.0 – 17.0', 'flag' => ''],
                ['label' => 'Total WBC count', 'value' => '11.6', 'unit' => '10³/µL', 'interval' => '4.0 – 11.0', 'flag' => 'H'],
                ['label' => 'Platelet count', 'value' => '248', 'unit' => '10³/µL', 'interval' => '150 – 410', 'flag' => ''],
            ],
        ]);

        return response($html);
    }

    /**
     * @return array<string, mixed>
     */
    private function design(Request $request): array
    {
        $data = $request->validate([
            'theme' => ['required', 'in:t1,t2,t3,t4,t5,t6'],
            'colors' => ['nullable', 'array'],
            'hide' => ['nullable', 'array'],
            'logo' => ['nullable', 'string', 'max:700000'],
            'sign' => ['nullable', 'string', 'max:700000'],
            'lab' => ['required', 'array'],
            'lab.name' => ['nullable', 'string', 'max:120'],
            'lab.tagline' => ['nullable', 'string', 'max:160'],
            'lab.address' => ['nullable', 'string', 'max:240'],
            'lab.phone' => ['nullable', 'string', 'max:80'],
            'lab.email' => ['nullable', 'string', 'max:120'],
            'lab.iso' => ['nullable', 'string', 'max:160'],
            'lab.footerNote' => ['nullable', 'string', 'max:240'],
            'lab.techName' => ['nullable', 'string', 'max:120'],
            'lab.techRole' => ['nullable', 'string', 'max:120'],
            'lab.doctor' => ['nullable', 'string', 'max:120'],
            'lab.qual' => ['nullable', 'string', 'max:160'],
        ]);

        $colors = [];
        foreach (['t1', 't2', 't3', 't4', 't5', 't6'] as $theme) {
            $chosen = $data['colors'][$theme] ?? [];
            if (! is_array($chosen)) {
                continue;
            }
            foreach (['c1', 'c2', 'c3'] as $slot) {
                $value = $chosen[$slot] ?? '';
                if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    $colors[$theme][$slot] = $value;
                }
            }
        }

        $hide = [];
        foreach (['qr', 'barcode', 'sign', 'tech', 'tagline', 'iso', 'contact', 'note', 'wm'] as $key) {
            $hide[$key] = (bool) (($data['hide'][$key] ?? false));
        }

        return [
            'theme' => $data['theme'],
            'colors' => $colors,
            'hide' => $hide,
            'logo' => is_string($data['logo'] ?? null) && str_starts_with($data['logo'], 'data:image/') ? $data['logo'] : '',
            'sign' => is_string($data['sign'] ?? null) && str_starts_with($data['sign'], 'data:image/') ? $data['sign'] : '',
            'lab' => [
                'name' => (string) ($data['lab']['name'] ?? ''),
                'tagline' => (string) ($data['lab']['tagline'] ?? ''),
                'address' => (string) ($data['lab']['address'] ?? ''),
                'phone' => (string) ($data['lab']['phone'] ?? ''),
                'email' => (string) ($data['lab']['email'] ?? ''),
                'iso' => (string) ($data['lab']['iso'] ?? ''),
                'footerNote' => (string) ($data['lab']['footerNote'] ?? ''),
                'techName' => (string) ($data['lab']['techName'] ?? ''),
                'techRole' => (string) ($data['lab']['techRole'] ?? ''),
                'doctor' => (string) ($data['lab']['doctor'] ?? ''),
                'qual' => (string) ($data['lab']['qual'] ?? ''),
            ],
        ];
    }
}
