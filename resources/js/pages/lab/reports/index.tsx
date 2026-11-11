import { Head, Link } from '@inertiajs/react';

type ReportRow = {
    id: number;
    report_number: string;
    version: number;
    status: string;
    checksum: string | null;
    order: { patient: { name: string; is_demo: boolean } };
};

export default function ReportsIndex({ reports }: { reports: ReportRow[] }) {
    return (
        <>
            <Head title="Reports" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Reports</h1>
                <div className="rounded-xl border">
                    {reports.map((report) => (
                        <Link
                            key={report.id}
                            href={`/reports/${report.id}`}
                            className="grid grid-cols-4 gap-2 border-b p-3 text-sm last:border-b-0"
                        >
                            <span className="font-medium">
                                {report.report_number} v{report.version}
                            </span>
                            <span>
                                {report.order.patient.name}
                                {report.order.patient.is_demo ? ' · DEMO' : ''}
                            </span>
                            <span>{report.status}</span>
                            <span className="truncate text-muted-foreground">
                                {report.checksum?.slice(0, 12)}
                            </span>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
