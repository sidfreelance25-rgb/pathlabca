import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type PatientRow = {
    id: number;
    patient_number: string;
    name: string;
    sex: string;
    phone: string | null;
    is_demo: boolean;
};

export default function PatientsIndex({
    patients,
    filters,
}: {
    patients: PatientRow[];
    filters: { q: string };
}) {
    return (
        <>
            <Head title="Patients" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">Patients</h1>
                    <Button asChild>
                        <Link href="/patients/create">Register</Link>
                    </Button>
                </div>
                <form
                    className="flex gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const data = new FormData(event.currentTarget);
                        router.get(
                            '/patients',
                            { q: data.get('q') },
                            { preserveState: true },
                        );
                    }}
                >
                    <Input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Name, ID or phone"
                    />
                    <Button type="submit" variant="outline">
                        Search
                    </Button>
                </form>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">ID</th>
                                <th className="p-3">Name</th>
                                <th className="p-3">Sex</th>
                                <th className="p-3">Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            {patients.map((patient) => (
                                <tr key={patient.id} className="border-t">
                                    <td className="p-3">
                                        <Link
                                            href={`/patients/${patient.id}`}
                                            className="font-medium underline"
                                        >
                                            {patient.patient_number}
                                        </Link>
                                    </td>
                                    <td className="p-3">
                                        {patient.name}
                                        {patient.is_demo ? ' · DEMO' : ''}
                                    </td>
                                    <td className="p-3">{patient.sex}</td>
                                    <td className="p-3">{patient.phone}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
