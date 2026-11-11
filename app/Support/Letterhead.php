<?php

namespace App\Support;

use App\Models\User;

class Letterhead
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(?User $user = null): array
    {
        $lab = $user?->lab;

        return [
            'theme' => 't1',
            'colors' => [],
            'hide' => [],
            'logo' => '',
            'sign' => '',
            'lab' => [
                'name' => $lab->name ?? '',
                'tagline' => 'Accurate | Caring | Instant',
                'address' => $lab->address ?? '',
                'phone' => $lab->phone ?? '',
                'email' => $lab->email ?? ($user->email ?? ''),
                'iso' => $lab?->licence_number ? 'Licence '.$lab->licence_number : '',
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
        $defaults = self::defaults($user);
        $saved = $user?->letterhead;

        if (! is_array($saved)) {
            return $defaults;
        }

        $lab = is_array($saved['lab'] ?? null) ? $saved['lab'] : [];

        return [
            'theme' => is_string($saved['theme'] ?? null) ? $saved['theme'] : $defaults['theme'],
            'colors' => is_array($saved['colors'] ?? null) ? $saved['colors'] : [],
            'hide' => is_array($saved['hide'] ?? null) ? $saved['hide'] : [],
            'logo' => is_string($saved['logo'] ?? null) ? $saved['logo'] : '',
            'sign' => is_string($saved['sign'] ?? null) ? $saved['sign'] : '',
            'lab' => array_merge($defaults['lab'], $lab),
        ];
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
