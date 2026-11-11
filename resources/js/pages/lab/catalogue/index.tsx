import { Head, Link, router } from '@inertiajs/react';
import { rupees } from '@/lib/lab';

type TestRow = {
    id: number;
    code: string;
    name: string;
    is_panel: boolean;
    price_paise: number;
    tat_minutes: number;
    department: { name: string };
    specimen_type: { name: string; container: string | null };
};

export default function CatalogueIndex({
    tests,
    departments,
    filters,
}: {
    tests: TestRow[];
    departments: { id: number; name: string }[];
    filters: { discipline: string };
}) {
    return (
        <>
            <Head title="Test catalogue" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Test catalogue</h1>
                    <p className="max-w-3xl text-sm text-muted-foreground">
                        Names, LOINC codes and starter intervals come from ABIM
                        (January 2026), ACCP CCSAP, and published LOINC tables.
                        They are not a local method validation.
                    </p>
                </div>
                <select
                    className="h-9 w-64 rounded-md border px-2"
                    value={filters.discipline}
                    onChange={(event) =>
                        router.get(
                            '/catalogue',
                            { discipline: event.target.value },
                            { preserveState: true },
                        )
                    }
                >
                    <option value="">All departments</option>
                    {departments.map((department) => (
                        <option key={department.id} value={department.id}>
                            {department.name}
                        </option>
                    ))}
                </select>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Test</th>
                                <th className="p-3">Department</th>
                                <th className="p-3">Specimen</th>
                                <th className="p-3">TAT</th>
                                <th className="p-3">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tests.map((test) => (
                                <tr key={test.id} className="border-t">
                                    <td className="p-3">
                                        <Link
                                            href={`/catalogue/${test.id}`}
                                            className="font-medium underline"
                                        >
                                            {test.name}
                                        </Link>
                                        <div className="text-muted-foreground">
                                            {test.code}
                                            {test.is_panel ? ' · panel' : ''}
                                        </div>
                                    </td>
                                    <td className="p-3">
                                        {test.department.name}
                                    </td>
                                    <td className="p-3">
                                        {test.specimen_type.name}
                                    </td>
                                    <td className="p-3">
                                        {test.tat_minutes} min
                                    </td>
                                    <td className="p-3">
                                        {rupees(test.price_paise)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
