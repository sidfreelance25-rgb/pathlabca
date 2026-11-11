import { Head, router, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { allows } from '@/lib/lab';

type Match = {
    id: number;
    barcode: string;
    status: string;
    specimen_type: { name: string };
    order: {
        order_number: string;
        patient: { name: string; patient_number: string; is_demo: boolean };
        items: { test: { name: string } }[];
    };
    events: { id: number; status: string; created_at: string }[];
} | null;

export default function AccessionScan({
    match,
    barcode,
}: {
    match: Match;
    barcode?: string;
}) {
    const form = useForm({ barcode: barcode ?? '' });
    const permissions = (
        usePage().props.auth as { user?: { permissions?: string[] } }
    ).user?.permissions;

    return (
        <>
            <Head title="Accession" />
            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Scan barcode</h1>
                <form
                    className="flex gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/accession');
                    }}
                >
                    <Input
                        autoFocus
                        value={form.data.barcode}
                        onChange={(event) =>
                            form.setData('barcode', event.target.value)
                        }
                        placeholder="Scan or type barcode"
                    />
                    <Button type="submit">Find</Button>
                </form>
                <InputError message={form.errors.barcode} />
                {barcode && match === null && (
                    <p className="text-sm">No specimen matches {barcode}.</p>
                )}
                {match && (
                    <section className="rounded-xl border p-4 text-sm">
                        <div className="text-lg font-semibold">
                            {match.barcode}
                        </div>
                        <p>
                            {match.order.patient.name} ·{' '}
                            {match.order.patient.patient_number}
                            {match.order.patient.is_demo ? ' · DEMO' : ''}
                        </p>
                        <p>
                            {match.order.order_number} ·{' '}
                            {match.specimen_type.name} · {match.status}
                        </p>
                        <p className="mt-2">
                            {match.order.items
                                .map((item) => item.test.name)
                                .join(', ')}
                        </p>
                        <ol className="mt-3 list-decimal pl-4 text-muted-foreground">
                            {match.events.map((event) => (
                                <li key={event.id}>{event.status}</li>
                            ))}
                        </ol>
                        {match.status === 'collected' &&
                            allows(permissions, 'specimen.receive') && (
                                <Button
                                    className="mt-3"
                                    onClick={() =>
                                        router.post(
                                            `/specimens/${match.id}/receive`,
                                            { condition: 'Acceptable' },
                                        )
                                    }
                                >
                                    Accession specimen
                                </Button>
                            )}
                    </section>
                )}
            </div>
        </>
    );
}
