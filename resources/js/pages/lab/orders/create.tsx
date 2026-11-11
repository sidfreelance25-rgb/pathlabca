import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { rupees } from '@/lib/lab';

type TestOption = {
    id: number;
    name: string;
    code: string;
    price_paise: number;
    is_panel: boolean;
    scope?: 'main' | 'copy' | 'personal';
    department: { name: string };
    specimen_type: { name: string };
};

export default function OrderCreate({
    patients,
    doctors,
    tests,
}: {
    patients: {
        id: number;
        name: string;
        patient_number: string;
        is_demo: boolean;
    }[];
    doctors: { id: number; name: string; qualification: string | null }[];
    tests: TestOption[];
}) {
    const form = useForm({
        patient_id: patients[0]?.id?.toString() ?? '',
        referring_doctor_id: doctors[0]?.id?.toString() ?? '',
        priority: 'routine',
        clinical_notes: '',
        test_ids: [] as number[],
    });

    const grouped = tests.reduce<Record<string, TestOption[]>>(
        (groups, test) => {
            const key = test.department?.name ?? 'Other';
            groups[key] = [...(groups[key] ?? []), test];
            return groups;
        },
        {},
    );

    return (
        <>
            <Head title="New order" />
            <form
                className="flex flex-col gap-4 p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/orders');
                }}
            >
                <h1 className="text-2xl font-semibold">New order</h1>
                <div className="grid gap-4 md:grid-cols-3">
                    <label className="grid gap-2 text-sm">
                        <Label>Patient</Label>
                        <select
                            className="h-9 rounded-md border px-2"
                            value={form.data.patient_id}
                            onChange={(event) =>
                                form.setData('patient_id', event.target.value)
                            }
                        >
                            {patients.map((patient) => (
                                <option key={patient.id} value={patient.id}>
                                    {patient.patient_number} · {patient.name}
                                    {patient.is_demo ? ' (DEMO)' : ''}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-2 text-sm">
                        <Label>Referring doctor</Label>
                        <select
                            className="h-9 rounded-md border px-2"
                            value={form.data.referring_doctor_id}
                            onChange={(event) =>
                                form.setData(
                                    'referring_doctor_id',
                                    event.target.value,
                                )
                            }
                        >
                            {doctors.map((doctor) => (
                                <option key={doctor.id} value={doctor.id}>
                                    {doctor.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="grid gap-2 text-sm">
                        <Label>Priority</Label>
                        <select
                            className="h-9 rounded-md border px-2"
                            value={form.data.priority}
                            onChange={(event) =>
                                form.setData('priority', event.target.value)
                            }
                        >
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </label>
                </div>
                <InputError message={form.errors.test_ids} />
                {Object.entries(grouped).map(([department, options]) => (
                    <section key={department}>
                        <h2 className="mb-2 font-medium">{department}</h2>
                        <div className="grid gap-2 md:grid-cols-2">
                            {options.map((test) => (
                                <label
                                    key={test.id}
                                    className="flex items-start gap-2 rounded-lg border p-3 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data.test_ids.includes(
                                            test.id,
                                        )}
                                        onChange={(event) => {
                                            form.setData(
                                                'test_ids',
                                                event.target.checked
                                                    ? [
                                                          ...form.data.test_ids,
                                                          test.id,
                                                      ]
                                                    : form.data.test_ids.filter(
                                                          (id) =>
                                                              id !== test.id,
                                                      ),
                                            );
                                        }}
                                    />
                                    <span>
                                        <span className="font-medium">
                                            {test.name}
                                        </span>
                                        {test.is_panel ? ' · panel' : ''}
                                        <span className="block text-muted-foreground">
                                            {test.specimen_type.name} ·{' '}
                                            {rupees(test.price_paise)}
                                            {test.scope === 'copy'
                                                ? ' · your copy'
                                                : ''}
                                            {test.scope === 'personal'
                                                ? ' · your test'
                                                : ''}
                                        </span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    </section>
                ))}
                <Button type="submit" disabled={form.processing}>
                    Place order and print identities
                </Button>
            </form>
        </>
    );
}
