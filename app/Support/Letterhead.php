<?php

namespace App\Support;

use App\Models\LetterheadTemplate;
use App\Models\User;

class Letterhead
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(?User $user = null): array
    {
        return self::defaultsFromLab($user?->lab, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFromLab(?object $lab, ?User $user = null): array
    {
        $licence = self::text($lab, 'licence_number');

        return [
            'theme' => 't1',
            'colors' => [],
            'hide' => [],
            'logo' => '',
            'sign' => '',
            'lab' => [
                'name' => self::text($lab, 'name'),
                'tagline' => 'Accurate | Caring | Instant',
                'address' => self::text($lab, 'address'),
                'phone' => self::text($lab, 'phone'),
                'email' => self::text($lab, 'email') !== '' ? self::text($lab, 'email') : ($user->email ?? ''),
                'iso' => $licence !== '' ? 'Licence '.$licence : '',
                'footerNote' => 'Open daily. Reference intervals must be confirmed for the local method.',
                'techName' => '',
                'techRole' => 'Lab Technician',
                'doctor' => $user->name ?? '',
                'qual' => $user->qualification ?? '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forUser(?User $user): array
    {
        $template = self::activeTemplate($user);

        if ($template instanceof LetterheadTemplate) {
            return self::normalize($template->design, $user);
        }

        return self::defaults($user);
    }

    public static function activeTemplate(?User $user): ?LetterheadTemplate
    {
        if ($user === null || $user->lab_id === null) {
            return null;
        }

        $active = $user->active_letterhead_id === null
            ? null
            : LetterheadTemplate::query()->find($user->active_letterhead_id);

        if ($active instanceof LetterheadTemplate && ($active->owner_user_id === null || $active->owner_user_id === $user->id)) {
            return $active;
        }

        return LetterheadTemplate::query()
            ->where('lab_id', $user->lab_id)
            ->whereNull('owner_user_id')
            ->orderByDesc('is_default')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    public static function normalize(array $saved, ?User $user): array
    {
        $defaults = self::defaults($user);
        $lab = is_array($saved['lab'] ?? null) ? $saved['lab'] : [];
        $merged = array_merge($defaults['lab'], $lab);

        foreach (['doctor', 'qual', 'techName'] as $key) {
            if (trim((string) ($merged[$key] ?? '')) === '') {
                $merged[$key] = $defaults['lab'][$key] ?? '';
            }
        }

        return [
            'theme' => is_string($saved['theme'] ?? null) ? $saved['theme'] : $defaults['theme'],
            'colors' => is_array($saved['colors'] ?? null) ? $saved['colors'] : [],
            'hide' => is_array($saved['hide'] ?? null) ? $saved['hide'] : [],
            'logo' => is_string($saved['logo'] ?? null) ? $saved['logo'] : '',
            'sign' => is_string($saved['sign'] ?? null) ? $saved['sign'] : '',
            'lab' => $merged,
        ];
    }

    private static function text(?object $source, string $key): string
    {
        $value = data_get($source, $key, '');

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $design
     * @return list<string>
     */
    public static function missing(array $design): array
    {
        $lab = is_array($design['lab'] ?? null) ? $design['lab'] : [];
        $labels = [
            'name' => 'Lab name',
            'address' => 'Lab address',
            'phone' => 'Phone',
            'email' => 'Email',
            'doctor' => 'Doctor name',
            'qual' => 'Doctor qualification',
        ];
        $missing = [];

        foreach ($labels as $key => $label) {
            if (trim((string) ($lab[$key] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $design
     * @return array{name: string, n1: string, n2: string, tagline: string, taglineWide: string, address: string, phone: string, email: string, iso: string, footerNote: string, techName: string, techRole: string, doctor: string, qual: string}
     */
    public static function texts(array $design): array
    {
        $lab = is_array($design['lab'] ?? null) ? $design['lab'] : [];
        $name = trim((string) ($lab['name'] ?? ''));
        $words = $name === '' ? [] : preg_split('/\s+/', $name);
        $words = is_array($words) ? $words : [];
        $tagline = (string) ($lab['tagline'] ?? '');

        return [
            'name' => $name,
            'n1' => (string) ($words[0] ?? ''),
            'n2' => implode(' ', array_slice($words, 1)),
            'tagline' => $tagline,
            'taglineWide' => (string) preg_replace('/\s*\|\s*/', '  |  ', $tagline),
            'address' => (string) ($lab['address'] ?? ''),
            'phone' => (string) ($lab['phone'] ?? ''),
            'email' => (string) ($lab['email'] ?? ''),
            'iso' => (string) ($lab['iso'] ?? ''),
            'footerNote' => (string) ($lab['footerNote'] ?? ''),
            'techName' => (string) ($lab['techName'] ?? ''),
            'techRole' => (string) ($lab['techRole'] ?? ''),
            'doctor' => (string) ($lab['doctor'] ?? ''),
            'qual' => (string) ($lab['qual'] ?? ''),
        ];
    }
}
