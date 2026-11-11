import { Head, Link, usePage } from '@inertiajs/react';

const cards = [
    ['orders', 'Orders', '/orders'],
    ['collection', 'Awaiting collection', '/collection'],
    ['accession', 'Awaiting accession', '/accession'],
    ['validation', 'Awaiting validation', '/orders'],
    ['reports', 'Released reports', '/reports'],
    ['critical', 'Open critical alerts', '/critical'],
] as const;

export default function Dashboard() {
    const { stats } = usePage().props as {
        stats?: Record<(typeof cards)[number][0], number>;
    };

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Laboratory today</h1>
                    <p className="text-sm text-muted-foreground">
                        A demonstration CBC report is already released for DEMO
                        Patient A. New orders follow the same path.
                    </p>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {cards.map(([key, label, href]) => (
                        <Link
                            key={key}
                            href={href}
                            className="rounded-xl border p-5 hover:bg-muted/40"
                        >
                            <div className="text-sm text-muted-foreground">
                                {label}
                            </div>
                            <div className="mt-2 text-3xl font-semibold">
                                {stats?.[key] ?? 0}
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
