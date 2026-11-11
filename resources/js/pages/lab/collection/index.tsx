import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { allows } from '@/lib/lab';

type SpecimenRow = {
    id: number;
    barcode: string;
    status: string;
    specimen_type: { name: string };
    order: {
        id: number;
        order_number: string;
        patient: { name: string; is_demo: boolean };
    };
};

export default function CollectionIndex({
    specimens,
}: {
    specimens: SpecimenRow[];
}) {
    const permissions = (
        usePage().props.auth as { user?: { permissions?: string[] } }
    ).user?.permissions;

    return (
        <>
            <Head title="Collection" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Collection queue</h1>
                <div className="rounded-xl border">
                    {specimens.map((specimen) => (
                        <div
                            key={specimen.id}
                            className="flex items-center justify-between border-b p-3 text-sm last:border-b-0"
                        >
                            <div>
                                <div className="font-medium">
                                    {specimen.barcode}
                                </div>
                                <div className="text-muted-foreground">
                                    {specimen.order.patient.name}
                                    {specimen.order.patient.is_demo
                                        ? ' · DEMO'
                                        : ''}{' '}
                                    · {specimen.specimen_type.name} ·{' '}
                                    {specimen.status}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                {allows(permissions, 'barcode.print') && (
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={`/specimens/${specimen.id}/label`}
                                        >
                                            Barcode label
                                        </Link>
                                    </Button>
                                )}
                                {specimen.status === 'pending' &&
                                    allows(permissions, 'specimen.collect') && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                router.post(
                                                    `/specimens/${specimen.id}/collect`,
                                                    {
                                                        collection_site: 'Arm',
                                                        condition: 'Acceptable',
                                                    },
                                                )
                                            }
                                        >
                                            Collect
                                        </Button>
                                    )}
                                <Button asChild size="sm" variant="ghost">
                                    <Link href={`/orders/${specimen.order.id}`}>
                                        Order
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
