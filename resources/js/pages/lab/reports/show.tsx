import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { allows } from '@/lib/lab';

type Snapshot = {
    is_demo?: boolean;
    status?: string;
    sections?: Record<
        string,
        {
            name: string;
            rows: {
                label: string;
                value: string;
                unit: string | null;
                interval: string;
                flag: string;
            }[];
        }[]
    >;
    signatory?: { name: string; qualification: string | null };
};

export default function ReportShow({
    report,
}: {
    report: {
        id: number;
        report_number: string;
        version: number;
        status: string;
        checksum: string | null;
        verification_token: string;
        correction_reason: string | null;
        snapshot: Snapshot;
        order_id: number;
    };
}) {
    const permissions = (
        usePage().props.auth as { user?: { permissions?: string[] } }
    ).user?.permissions;
    const correction = useForm({ reason: '' });
    const sections = report.snapshot.sections ?? {};

    return (
        <>
            <Head title={`${report.report_number} v${report.version}`} />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {report.report_number} · version {report.version}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {report.status} · checksum{' '}
                            {report.checksum?.slice(0, 16)}
                            {report.snapshot.is_demo
                                ? ' · DEMONSTRATION, delivery blocked'
                                : ''}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {allows(permissions, 'report.download') && (
                            <Button asChild>
                                <a href={`/reports/${report.id}/download`}>
                                    Download PDF
                                </a>
                            </Button>
                        )}
                        {report.status === 'released' &&
                            !report.snapshot.is_demo &&
                            allows(permissions, 'report.release') && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            `/reports/${report.id}/deliver`,
                                            { channel: 'in_app' },
                                        )
                                    }
                                >
                                    Record delivery
                                </Button>
                            )}
                    </div>
                </div>
                {Object.entries(sections).map(([name, panels]) => (
                    <section key={name} className="rounded-xl border">
                        <h2 className="border-b p-3 font-medium">{name}</h2>
                        {panels.map((panel) => (
                            <div key={panel.name} className="p-3 text-sm">
                                <div className="mb-1 font-medium">
                                    {panel.name}
                                </div>
                                {panel.rows.map((row) => (
                                    <div
                                        key={row.label}
                                        className="grid grid-cols-5 gap-2 border-t py-1"
                                    >
                                        <span>{row.label}</span>
                                        <span>{row.value}</span>
                                        <span>{row.unit}</span>
                                        <span>{row.interval}</span>
                                        <span>{row.flag}</span>
                                    </div>
                                ))}
                            </div>
                        ))}
                    </section>
                ))}
                <p className="text-sm">
                    Signatory: {report.snapshot.signatory?.name}{' '}
                    {report.snapshot.signatory?.qualification}
                </p>
                {allows(permissions, 'report.correct') &&
                    report.status === 'released' && (
                        <form
                            className="flex gap-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                correction.post(
                                    `/reports/${report.id}/correct`,
                                );
                            }}
                        >
                            <Input
                                value={correction.data.reason}
                                onChange={(event) =>
                                    correction.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                                placeholder="Reason for correction"
                            />
                            <Button type="submit" variant="outline">
                                Start correction
                            </Button>
                        </form>
                    )}
                {allows(permissions, 'report.withdraw') && (
                    <Button
                        variant="destructive"
                        onClick={() => {
                            const reason = window.prompt(
                                'Reason for withdrawal',
                            );
                            if (reason) {
                                router.post(`/reports/${report.id}/withdraw`, {
                                    reason,
                                });
                            }
                        }}
                    >
                        Withdraw
                    </Button>
                )}
            </div>
        </>
    );
}
