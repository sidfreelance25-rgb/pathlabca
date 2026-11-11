import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { allows } from '@/lib/lab';

type Alert = {
    id: number;
    status: string;
    result: {
        value: string | null;
        flag: string | null;
        definition: { label: string; unit: string | null };
        order_item: {
            order: { order_number: string; patient: { name: string } };
        };
    };
};

export default function CriticalIndex({ alerts }: { alerts: Alert[] }) {
    const permissions = (
        usePage().props.auth as { user?: { permissions?: string[] } }
    ).user?.permissions;

    return (
        <>
            <Head title="Critical results" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Critical results</h1>
                {alerts.map((alert) => (
                    <AlertCard
                        key={alert.id}
                        alert={alert}
                        canAcknowledge={allows(
                            permissions,
                            'critical.acknowledge',
                        )}
                    />
                ))}
                {alerts.length === 0 && (
                    <p className="text-sm">No critical alerts.</p>
                )}
            </div>
        </>
    );
}

function AlertCard({
    alert,
    canAcknowledge,
}: {
    alert: Alert;
    canAcknowledge: boolean;
}) {
    const form = useForm({ note: '' });

    return (
        <form
            className="rounded-xl border p-4 text-sm"
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/critical/${alert.id}/acknowledge`);
            }}
        >
            <div className="font-medium">
                {alert.result.definition.label}: {alert.result.value}{' '}
                {alert.result.definition.unit} · {alert.result.flag}
            </div>
            <div className="text-muted-foreground">
                {alert.result.order_item.order.patient.name} ·{' '}
                {alert.result.order_item.order.order_number} · {alert.status}
            </div>
            {alert.status === 'open' && canAcknowledge && (
                <div className="mt-3 flex gap-2">
                    <Input
                        value={form.data.note}
                        onChange={(event) =>
                            form.setData('note', event.target.value)
                        }
                        placeholder="Who was informed, and when"
                    />
                    <Button type="submit">Acknowledge</Button>
                </div>
            )}
        </form>
    );
}
