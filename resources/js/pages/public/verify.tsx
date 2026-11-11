import { Head } from '@inertiajs/react';

export default function VerifyReport({
    authentic,
    summary,
}: {
    authentic: boolean;
    summary: {
        report_number: string;
        version: number;
        status: string;
        released_at: string | null;
        lab: string;
        checksum_hint: string | null;
    } | null;
}) {
    return (
        <>
            <Head title="Verify report" />
            <main className="mx-auto flex min-h-screen max-w-lg flex-col justify-center gap-4 px-6">
                <p className="text-sm text-[#5c5a55]">
                    PathLabCA report verification
                </p>
                <h1 className="text-3xl font-semibold">
                    {authentic
                        ? 'Authentic released report'
                        : 'Not a released report'}
                </h1>
                {summary ? (
                    <dl className="grid grid-cols-2 gap-2 text-sm">
                        <dt>Laboratory</dt>
                        <dd>{summary.lab}</dd>
                        <dt>Report</dt>
                        <dd>
                            {summary.report_number} v{summary.version}
                        </dd>
                        <dt>Status</dt>
                        <dd>{summary.status}</dd>
                        <dt>Released</dt>
                        <dd>{summary.released_at ?? '—'}</dd>
                        <dt>Checksum</dt>
                        <dd>{summary.checksum_hint}</dd>
                    </dl>
                ) : (
                    <p>This verification code does not match a report.</p>
                )}
                <p className="text-sm text-[#5c5a55]">
                    This page confirms authenticity only. It does not show
                    patient details or results.
                </p>
            </main>
        </>
    );
}
