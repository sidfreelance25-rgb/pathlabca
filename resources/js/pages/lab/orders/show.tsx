import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { allows, rupees } from '@/lib/lab';

type OrderShowProps = {
    order: {
        id: number;
        order_number: string;
        status: string;
        priority: string;
        clinical_notes: string | null;
        patient: {
            id: number;
            name: string;
            patient_number: string;
            is_demo: boolean;
            sex: string;
        };
        doctor: { name: string } | null;
        invoice: {
            id: number;
            number: string;
            amount_paise: number;
            status: string;
        } | null;
        specimens: {
            id: number;
            barcode: string;
            status: string;
            specimen_type: { name: string };
        }[];
        items: {
            id: number;
            test: { name: string };
            results: {
                flag: string | null;
                value: string | null;
                definition: { label: string };
            }[];
        }[];
        reports: {
            id: number;
            report_number: string;
            version: number;
            status: string;
        }[];
    };
};

export default function OrderShow({ order }: OrderShowProps) {
    const permissions = (
        usePage().props.auth as { user?: { permissions?: string[] } }
    ).user?.permissions;

    return (
        <>
            <Head title={order.order_number} />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {order.order_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {order.patient.name} ·{' '}
                            {order.patient.patient_number} · {order.priority} ·{' '}
                            {order.status}
                            {order.patient.is_demo ? ' · DEMONSTRATION' : ''}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {allows(permissions, 'result.enter') && (
                            <Button asChild>
                                <Link href={`/orders/${order.id}/results`}>
                                    Enter results
                                </Link>
                            </Button>
                        )}
                        {allows(permissions, 'result.review') && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.post(`/orders/${order.id}/review`)
                                }
                            >
                                Technical review
                            </Button>
                        )}
                        {allows(permissions, 'result.validate') && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.post(`/orders/${order.id}/validate`)
                                }
                            >
                                Validate
                            </Button>
                        )}
                        {allows(permissions, 'report.read') && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.post(`/orders/${order.id}/report`)
                                }
                            >
                                Draft PDF
                            </Button>
                        )}
                        {allows(permissions, 'report.release') && (
                            <Button
                                onClick={() =>
                                    router.post(`/orders/${order.id}/release`)
                                }
                            >
                                Release report
                            </Button>
                        )}
                    </div>
                </div>
                {order.invoice && (
                    <div className="flex items-center justify-between rounded-xl border p-3 text-sm">
                        <span>
                            {order.invoice.number} ·{' '}
                            {rupees(order.invoice.amount_paise)} ·{' '}
                            {order.invoice.status}
                        </span>
                        {order.invoice.status !== 'paid' &&
                            allows(permissions, 'invoice.write') && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            `/invoices/${order.invoice?.id}/pay`,
                                        )
                                    }
                                >
                                    Record payment
                                </Button>
                            )}
                    </div>
                )}
                <section className="rounded-xl border">
                    <h2 className="border-b p-3 font-medium">
                        Specimens and barcodes
                    </h2>
                    {order.specimens.map((specimen) => (
                        <div
                            key={specimen.id}
                            className="flex items-center justify-between border-b p-3 text-sm last:border-b-0"
                        >
                            <div>
                                <div className="font-medium">
                                    {specimen.barcode}
                                </div>
                                <div className="text-muted-foreground">
                                    {specimen.specimen_type.name} ·{' '}
                                    {specimen.status}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                {allows(permissions, 'barcode.print') && (
                                    <Button asChild size="sm" variant="outline">
                                        <Link
                                            href={`/specimens/${specimen.id}/label`}
                                        >
                                            Label
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
                            </div>
                        </div>
                    ))}
                </section>
                <section className="rounded-xl border p-3 text-sm">
                    <h2 className="mb-2 font-medium">Ordered tests</h2>
                    {order.items.map((item) => (
                        <div key={item.id} className="mb-2">
                            <div className="font-medium">{item.test.name}</div>
                            {item.results.map((result) => (
                                <div
                                    key={result.definition.label}
                                    className="text-muted-foreground"
                                >
                                    {result.definition.label}: {result.value}{' '}
                                    {result.flag}
                                </div>
                            ))}
                        </div>
                    ))}
                </section>
                <section className="rounded-xl border p-3 text-sm">
                    <h2 className="mb-2 font-medium">Report versions</h2>
                    {order.reports.map((report) => (
                        <Link
                            key={report.id}
                            href={`/reports/${report.id}`}
                            className="block underline"
                        >
                            {report.report_number} v{report.version} ·{' '}
                            {report.status}
                        </Link>
                    ))}
                    {order.reports.length === 0 && <p>No report yet.</p>}
                </section>
            </div>
        </>
    );
}
