import { Head, Link } from '@inertiajs/react';
import { login } from '@/routes';

export default function Welcome() {
    return (
        <>
            <Head title="PathLabCA" />
            <main className="mx-auto flex min-h-screen max-w-5xl flex-col justify-center gap-10 px-6 py-16">
                <div className="max-w-2xl">
                    <p className="text-sm font-medium tracking-wide text-[#b45309]">
                        Pathology laboratory
                    </p>
                    <h1 className="mt-2 text-4xl font-semibold tracking-tight">
                        PathLabCA
                    </h1>
                    <p className="mt-4 text-lg text-[#5c5a55]">
                        Register patients, barcode specimens, enter structured
                        results, and release versioned PDF reports. Reference
                        intervals are cited starter values and must be confirmed
                        for the local method before clinical use.
                    </p>
                    <Link
                        href={login()}
                        className="mt-6 inline-flex h-10 items-center rounded-md bg-[#1b1b18] px-4 text-sm font-medium text-white"
                    >
                        Staff sign in
                    </Link>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    {[
                        ['Lab admin', 'admin@pathlabca.test'],
                        ['Reception', 'reception@pathlabca.test'],
                        ['Phlebotomist', 'collect@pathlabca.test'],
                        ['Technician', 'tech@pathlabca.test'],
                        ['Pathologist', 'sign@pathlabca.test'],
                    ].map(([role, email]) => (
                        <div
                            key={email}
                            className="rounded-xl border px-4 py-3 text-sm"
                        >
                            <div className="font-medium">{role}</div>
                            <div className="text-[#5c5a55]">
                                {email} · password
                            </div>
                        </div>
                    ))}
                </div>
            </main>
        </>
    );
}
