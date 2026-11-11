import { Head, Link, usePage } from '@inertiajs/react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import {
    ClipboardList,
    FilePlus2,
    FlaskConical,
    LayoutGrid,
    SwatchBook,
    Users,
} from 'lucide-react';

type RecentReport = {
    id: number;
    patient: string;
    tests: string;
    status: string;
    date: string | null;
};

const actions = [
    {
        title: 'New report',
        description: 'Register patient results and create a report',
        href: '/orders/create',
        icon: FilePlus2,
    },
    {
        title: 'View reports',
        description: 'Browse, release, and download PDFs',
        href: '/reports',
        icon: ClipboardList,
    },
    {
        title: 'Patients',
        description: 'Find records or register a new patient',
        href: '/patients',
        icon: Users,
    },
    {
        title: 'Test catalogue',
        description: 'Manage main and personal tests',
        href: '/catalogue',
        icon: FlaskConical,
    },
    {
        title: 'Letterheads',
        description: 'Design templates for printed reports',
        href: '/settings/letterhead',
        icon: SwatchBook,
    },
];

export default function Dashboard() {
    const page = usePage();
    const stats = page.props.stats as
        | {
              generated_today: number;
              registered: number;
              awaiting: number;
              released: number;
          }
        | undefined;
    const recent = (page.props.recent as RecentReport[] | undefined) ?? [];
    const user = page.props.auth?.user;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-8 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2 text-lg font-semibold">
                        <span className="flex size-8 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                            <LayoutGrid className="size-4" />
                        </span>
                        PathLabCA
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <AppearanceToggleTab />
                        {user && (
                            <div className="flex items-center gap-2 text-sm">
                                <span className="flex size-8 items-center justify-center rounded-full bg-sky-600 text-xs font-semibold text-white">
                                    {user.name.slice(0, 1)}
                                </span>
                                <span>
                                    <span className="block leading-tight font-medium">
                                        {user.name}
                                    </span>
                                    <span className="text-muted-foreground capitalize">
                                        {typeof user.role === 'string'
                                            ? user.role.replaceAll('_', ' ')
                                            : 'staff'}
                                    </span>
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Stat
                        href="/reports"
                        value={stats?.generated_today ?? 0}
                        label="Generated today"
                        tone="text-sky-700"
                    />
                    <Stat
                        href="/patients"
                        value={stats?.registered ?? 0}
                        label="Registered"
                        tone="text-emerald-600"
                    />
                    <Stat
                        href="/orders"
                        value={stats?.awaiting ?? 0}
                        label="Awaiting completion"
                        tone="text-orange-500"
                    />
                    <Stat
                        href="/reports"
                        value={stats?.released ?? 0}
                        label="Released"
                        tone="text-violet-600"
                    />
                </div>

                <section>
                    <h2 className="mb-3 text-xs font-semibold tracking-wide text-muted-foreground">
                        QUICK ACTIONS
                    </h2>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {actions.map((action) => (
                            <Link
                                key={action.href}
                                href={action.href}
                                className="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-sm transition hover:border-sky-200 hover:bg-sky-50/40"
                            >
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <action.icon className="size-5" />
                                </span>
                                <span>
                                    <span className="block font-medium">
                                        {action.title}
                                    </span>
                                    <span className="block text-sm text-muted-foreground">
                                        {action.description}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>

                <section>
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-xs font-semibold tracking-wide text-muted-foreground">
                            RECENT REPORTS
                        </h2>
                        <Link
                            href="/reports"
                            className="text-sm text-sky-700 hover:underline"
                        >
                            View all
                        </Link>
                    </div>
                    <div className="overflow-hidden rounded-2xl border bg-card">
                        {recent.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                No reports yet. Start with New report.
                            </p>
                        )}
                        {recent.map((report) => (
                            <Link
                                key={report.id}
                                href={`/reports/${report.id}`}
                                className="flex items-center justify-between gap-4 border-b px-4 py-3 last:border-b-0 hover:bg-muted/40"
                            >
                                <span>
                                    <span className="block font-medium">
                                        {report.patient}
                                    </span>
                                    <span className="block text-sm text-muted-foreground">
                                        {report.tests}
                                    </span>
                                </span>
                                <span className="text-right text-sm">
                                    <span className="block text-muted-foreground">
                                        {report.date}
                                    </span>
                                    <span className="capitalize">
                                        {report.status}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

function Stat({
    href,
    value,
    label,
    tone,
}: {
    href: string;
    value: number;
    label: string;
    tone: string;
}) {
    return (
        <Link
            href={href}
            className="rounded-2xl border bg-card p-5 shadow-sm hover:bg-muted/30"
        >
            <div className={`text-3xl font-semibold ${tone}`}>{value}</div>
            <div className="mt-1 text-sm text-muted-foreground">{label}</div>
        </Link>
    );
}
