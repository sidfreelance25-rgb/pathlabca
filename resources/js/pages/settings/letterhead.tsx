import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LabFields = {
    name: string;
    tagline: string;
    address: string;
    phone: string;
    email: string;
    iso: string;
    footerNote: string;
    techName: string;
    techRole: string;
    doctor: string;
    qual: string;
};

type Design = {
    theme: string;
    colors: Record<string, { c1?: string; c2?: string; c3?: string }>;
    hide: Record<string, boolean>;
    logo: string;
    sign: string;
    lab: LabFields;
};

const themes = [
    ['t1', '1 · Green swoosh'],
    ['t2', '2 · Blue corners'],
    ['t3', '3 · Navy and orange'],
    ['t4', '4 · Pink and blue'],
    ['t5', '5 · Blue bar'],
    ['t6', '6 · Hexagons'],
];

const fields: [keyof LabFields, string][] = [
    ['name', 'Lab name'],
    ['tagline', 'Tagline (use | between words)'],
    ['address', 'Address'],
    ['phone', 'Phone'],
    ['email', 'Email'],
    ['iso', 'Certification text'],
    ['techName', 'Technician name'],
    ['techRole', 'Technician title'],
    ['doctor', 'Doctor name'],
    ['qual', 'Doctor qualification'],
    ['footerNote', 'Footer note'],
];

const toggles: [string, string][] = [
    ['qr', 'QR code'],
    ['barcode', 'Barcode'],
    ['sign', 'Signature'],
    ['tech', 'Technician'],
    ['tagline', 'Tagline'],
    ['iso', 'Certification'],
    ['contact', 'Phone and email'],
    ['note', 'Footer note'],
    ['wm', 'Watermark'],
];

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function readImage(file: File, max: number, done: (value: string) => void): void {
    const reader = new FileReader();
    reader.onload = () => {
        const image = new Image();
        image.onload = () => {
            const scale = Math.min(1, max / Math.max(image.width, image.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(image.width * scale);
            canvas.height = Math.round(image.height * scale);
            canvas.getContext('2d')?.drawImage(image, 0, 0, canvas.width, canvas.height);
            done(canvas.toDataURL('image/png'));
        };
        image.src = String(reader.result);
    };
    reader.readAsDataURL(file);
}

export default function LetterheadSettings({
    design,
    missing,
}: {
    design: Design;
    missing: string[];
}) {
    const [draft, setDraft] = useState<Design>(design);
    const [html, setHtml] = useState('');
    const colors = draft.colors[draft.theme] ?? {};

    useEffect(() => {
        const timer = window.setTimeout(() => {
            void fetch('/settings/letterhead/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/html',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(draft),
            })
                .then((response) => response.text())
                .then(setHtml);
        }, 250);

        return () => window.clearTimeout(timer);
    }, [draft]);

    const setLab = (key: keyof LabFields, value: string) => {
        setDraft({ ...draft, lab: { ...draft.lab, [key]: value } });
    };

    return (
        <>
            <Head title="Letterhead" />
            <div className="grid items-start gap-6 xl:grid-cols-[340px_1fr]">
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.put('/settings/letterhead', draft);
                    }}
                >
                    <Heading variant="small" title="Letterhead" description="Changes apply only to your account. The sheet on the right updates as you type." />
                    {missing.length > 0 && (
                        <p className="rounded-md bg-amber-50 p-3 text-sm text-amber-800">Still needed: {missing.join(', ')}.</p>
                    )}
                    <label className="grid gap-1 text-sm">
                        <Label>Design</Label>
                        <select className="h-9 rounded-md border px-2" value={draft.theme} onChange={(event) => setDraft({ ...draft, theme: event.target.value })}>
                            {themes.map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </label>
                    <div className="flex gap-3 text-sm">
                        {([['c1', 'Main'], ['c2', 'Accent'], ['c3', 'Dark']] as const).map(([slot, label]) => (
                            <label key={slot}>{label}
                                <input type="color" className="mt-1 block h-8 w-12" value={colors[slot] ?? '#0a7a3e'} onChange={(event) => setDraft({
                                    ...draft,
                                    colors: { ...draft.colors, [draft.theme]: { ...colors, [slot]: event.target.value } },
                                })} />
                            </label>
                        ))}
                    </div>
                    {fields.map(([key, label]) => (
                        <label key={key} className="grid gap-1 text-sm">
                            <Label>{label}</Label>
                            <Input value={draft.lab[key]} onChange={(event) => setLab(key, event.target.value)} />
                        </label>
                    ))}
                    <div className="grid grid-cols-2 gap-2 text-sm">
                        {toggles.map(([key, label]) => (
                            <label key={key} className="flex items-center gap-2">
                                <input type="checkbox" checked={!draft.hide[key]} onChange={(event) => setDraft({ ...draft, hide: { ...draft.hide, [key]: !event.target.checked } })} />
                                {label}
                            </label>
                        ))}
                    </div>
                    <div className="flex gap-2">
                        <label className="text-sm">
                            Logo
                            <input type="file" accept="image/*" className="block text-xs" onChange={(event) => {
                                const file = event.target.files?.[0];
                                if (file) {
                                    readImage(file, 500, (logo) => setDraft({ ...draft, logo }));
                                }
                            }} />
                        </label>
                        <label className="text-sm">
                            Signature
                            <input type="file" accept="image/*" className="block text-xs" onChange={(event) => {
                                const file = event.target.files?.[0];
                                if (file) {
                                    readImage(file, 600, (sign) => setDraft({ ...draft, sign }));
                                }
                            }} />
                        </label>
                    </div>
                    <Button type="submit">Save my letterhead</Button>
                </form>
                <iframe title="Letterhead preview" className="h-[1120px] w-full rounded-xl border bg-neutral-200" srcDoc={html} />
            </div>
        </>
    );
}

LetterheadSettings.layout = {
    breadcrumbs: [
        { title: 'Letterhead', href: '/settings/letterhead' },
    ],
};
