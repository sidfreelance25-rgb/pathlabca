<?php

namespace App\Services;

use App\Models\LetterheadTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class LetterheadLibrary
{
    /**
     * @return Collection<int, LetterheadTemplate>
     */
    public function visible(User $user): Collection
    {
        return LetterheadTemplate::query()
            ->where('lab_id', $user->lab_id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('owner_user_id')->orWhere('owner_user_id', $user->id);
            })
            ->orderByRaw('owner_user_id is null desc')
            ->orderBy('name')
            ->get();
    }

    public function findVisible(User $user, ?int $id): ?LetterheadTemplate
    {
        if ($id === null) {
            return null;
        }

        return $this->visible($user)->firstWhere('id', $id);
    }

    public function active(User $user): ?LetterheadTemplate
    {
        $active = $this->findVisible($user, $user->active_letterhead_id);

        if ($active instanceof LetterheadTemplate) {
            return $active;
        }

        return $this->visible($user)->first(fn (LetterheadTemplate $template): bool => $template->is_default && $template->owner_user_id === null)
            ?? $this->visible($user)->first(fn (LetterheadTemplate $template): bool => $template->owner_user_id === null);
    }

    /**
     * @param  array<string, mixed>  $design
     */
    public function save(User $user, ?int $templateId, array $design, string $name, string $intent, bool $makeDefault): LetterheadTemplate
    {
        abort_if($user->lab_id === null, 403);
        $template = $this->findVisible($user, $templateId);

        if ($intent === 'new_global') {
            abort_unless($user->isLabAdmin(), 403);

            return $this->store($user, null, null, $name, $design, $makeDefault, false);
        }

        if ($intent === 'copy' || ($template instanceof LetterheadTemplate && $template->owner_user_id === null && ! $user->isLabAdmin())) {
            $existing = $template === null ? null : LetterheadTemplate::query()
                ->where('owner_user_id', $user->id)
                ->where('source_letterhead_id', $template->id)
                ->first();

            if ($existing instanceof LetterheadTemplate) {
                $existing->update(['name' => $name, 'design' => $design]);
                $user->update(['active_letterhead_id' => $existing->id]);
                $this->rememberQualification($user, $design);

                return $existing;
            }

            $saved = $this->store($user, $user->id, $template?->id, $name, $design, false, true);
            $this->rememberQualification($user, $design);

            return $saved;
        }

        if ($template instanceof LetterheadTemplate && $template->owner_user_id === $user->id) {
            $template->update(['name' => $name, 'design' => $design]);
            $user->update(['active_letterhead_id' => $template->id]);
            $this->rememberQualification($user, $design);

            return $template;
        }

        if ($template instanceof LetterheadTemplate && $template->owner_user_id === null && $user->isLabAdmin()) {
            if ($makeDefault) {
                $this->clearDefault($user);
            }
            $template->update([
                'name' => $name,
                'design' => $design,
                'is_default' => $makeDefault || $template->is_default,
            ]);

            return $template;
        }

        $ownerId = $user->isLabAdmin() ? null : $user->id;

        return $this->store($user, $ownerId, null, $name, $design, $ownerId === null, $ownerId !== null);
    }

    public function activate(User $user, LetterheadTemplate $template): void
    {
        abort_unless($this->findVisible($user, $template->id) instanceof LetterheadTemplate, 403);
        $user->update(['active_letterhead_id' => $template->id]);
        $this->rememberQualification($user, $template->design);
    }

    /**
     * @param  array<string, mixed>  $design
     */
    private function store(User $user, ?int $ownerId, ?int $sourceId, string $name, array $design, bool $makeDefault, bool $activate): LetterheadTemplate
    {
        if ($makeDefault) {
            $this->clearDefault($user);
        }

        $template = LetterheadTemplate::query()->create([
            'lab_id' => $user->lab_id,
            'owner_user_id' => $ownerId,
            'source_letterhead_id' => $sourceId,
            'name' => $name,
            'design' => $design,
            'is_default' => $ownerId === null && $makeDefault,
        ]);

        if ($activate) {
            $user->update(['active_letterhead_id' => $template->id]);
        }

        return $template;
    }

    private function clearDefault(User $user): void
    {
        LetterheadTemplate::query()
            ->where('lab_id', $user->lab_id)
            ->whereNull('owner_user_id')
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $design
     */
    private function rememberQualification(User $user, array $design): void
    {
        $lab = is_array($design['lab'] ?? null) ? $design['lab'] : [];
        $qualification = trim((string) ($lab['qual'] ?? ''));

        if ($qualification !== '') {
            $user->update(['qualification' => $qualification]);
        }
    }
}
