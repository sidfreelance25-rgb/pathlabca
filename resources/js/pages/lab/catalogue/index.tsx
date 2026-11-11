import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { rupees } from '@/lib/lab';

type TestRow = {
    id: number;
    code: string;
    name: string;
    is_panel: boolean;
    price_paise: number;
    tat_minutes: number;
    personal: boolean;
    custom: boolean;
    department: { name: string };
    specimen_type: { name: string; container: string | null };
};

export default function CatalogueIndex({
    tests,
    departments,
    specimens,
    filters,
}: {
    tests: TestRow[];
    departments: { id: number; name: string }[];
    specimens: { id: number; name: string }[];
    filters: { discipline: string };
}) {
    const created = useForm({
        name: '',
        code: '',
        department_id: String(departments[0]?.id ?? ''),
        specimen_type_id: String(specimens[0]?.id ?? ''),
        price_rupees: '100',
        unit: '',
    });
    return (
        <>
            <Head title="Test catalogue" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Test catalogue</h1>
                    <p className="max-w-3xl text-sm text-muted-foreground">
                        A price or name you save here belongs to your account.
                        Other staff keep the shared catalogue until they save their own.
                    </p>
                </div>
                <form
                    className="grid gap-2 rounded-xl border p-3 md:grid-cols-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        created.post('/catalogue');
                    }}
                >
                    <Input placeholder="New test name" value={created.data.name} onChange={(event) => created.setData('name', event.target.value)} />
                    <Input placeholder="Code" value={created.data.code} onChange={(event) => created.setData('code', event.target.value)} />
                    <select className="h-9 rounded-md border px-2" value={created.data.department_id} onChange={(event) => created.setData('department_id', event.target.value)}>
                        {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                    </select>
                    <select className="h-9 rounded-md border px-2" value={created.data.specimen_type_id} onChange={(event) => created.setData('specimen_type_id', event.target.value)}>
                        {specimens.map((specimen) => <option key={specimen.id} value={specimen.id}>{specimen.name}</option>)}
                    </select>
                    <Input placeholder="Price ₹" value={created.data.price_rupees} onChange={(event) => created.setData('price_rupees', event.target.value)} />
                    <Button type="submit">Add my test</Button>
                </form>
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
                                        <form
                                            className="flex items-center gap-2"
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                const price = new FormData(event.currentTarget).get('price_rupees');
                                                router.patch(`/catalogue/${test.id}`, {
                                                    name: test.name,
                                                    price_rupees: price,
                                                    active: true,
                                                });
                                            }}
                                        >
                                            <Input name="price_rupees" className="h-8 w-24" defaultValue={String(test.price_paise / 100)} />
                                            <Button type="submit" size="sm" variant="outline">My price</Button>
                                        </form>
                                        {test.personal && (
                                            <div className="text-xs text-muted-foreground">{test.custom ? 'Your test' : 'Your price'}</div>
                                        )}
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
