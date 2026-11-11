import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { rupees } from '@/lib/lab';

type PatientOption = {
    id: number;
    name: string;
    patient_number: string;
    sex: string;
    date_of_birth: string | null;
    phone: string | null;
    is_demo: boolean;
};

type TestOption = {
    id: number;
    name: string;
    code: string;
    price_paise: number;
    is_panel: boolean;
    scope?: 'main' | 'copy' | 'personal';
    department: { name: string } | null;
    specimen_type: { name: string } | null;
};

function ageLabel(date: string | null): string {
    if (!date) {
        return 'Age not recorded';
    }

    const born = new Date(date);
    if (Number.isNaN(born.getTime())) {
        return date;
    }

    const years = Math.floor(
        (Date.now() - born.getTime()) / (365.25 * 24 * 60 * 60 * 1000),
    );

    return `${years} years · ${date}`;
}

export default function OrderCreate({
    patients,
    doctors,
    tests,
    canEnterResults,
}: {
    patients: PatientOption[];
    doctors: { id: number; name: string; qualification: string | null }[];
    tests: TestOption[];
    canEnterResults: boolean;
}) {
    const [patientQuery, setPatientQuery] = useState('');
    const [patientOpen, setPatientOpen] = useState(false);
    const [testQuery, setTestQuery] = useState('');
    const form = useForm({
        patient_mode: 'existing' as 'new' | 'existing',
        patient_id: '',
        patient: {
            name: '',
            sex: 'female',
            date_of_birth: '',
            phone: '',
        },
        referring_doctor_id: doctors[0]?.id?.toString() ?? '',
        priority: 'routine',
        clinical_notes: '',
        test_ids: [] as number[],
    });

    const chosen = patients.find(
        (patient) => String(patient.id) === form.data.patient_id,
    );
    const patientMatches = useMemo(() => {
        const query = patientQuery.trim().toLowerCase();
        if (query === '') {
            return [];
        }

        return patients
            .filter((patient) =>
                `${patient.name} ${patient.patient_number} ${patient.phone ?? ''}`
                    .toLowerCase()
                    .includes(query),
            )
            .slice(0, 8);
    }, [patientQuery, patients]);
    const selectedTests = tests.filter((test) =>
        form.data.test_ids.includes(test.id),
    );
    const testMatches = useMemo(() => {
        const query = testQuery.trim().toLowerCase();
        if (query === '') {
            return [];
        }

        return tests
            .filter(
                (test) =>
                    !form.data.test_ids.includes(test.id) &&
                    `${test.name} ${test.code}`.toLowerCase().includes(query),
            )
            .slice(0, 8);
    }, [form.data.test_ids, testQuery, tests]);

    const chooseTest = (test: TestOption) => {
        form.setData('test_ids', [...form.data.test_ids, test.id]);
        setTestQuery('');
    };

    return (
        <>
            <Head title="New report" />
            <form
                className="mx-auto flex max-w-3xl flex-col gap-6 p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/orders');
                }}
            >
                <div>
                    <h1 className="text-2xl font-semibold">New report</h1>
                    <p className="text-sm text-muted-foreground">
                        Choose the patient, search for the tests, then enter the
                        results.
                    </p>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="font-medium">1. Patient</h2>
                    <div className="flex gap-2">
                        {(
                            [
                                ['existing', 'Existing patient'],
                                ['new', 'New patient'],
                            ] as const
                        ).map(([value, label]) => (
                            <Button
                                key={value}
                                type="button"
                                variant={
                                    form.data.patient_mode === value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() =>
                                    form.setData('patient_mode', value)
                                }
                            >
                                {label}
                            </Button>
                        ))}
                    </div>

                    {form.data.patient_mode === 'existing' ? (
                        <div className="relative grid gap-2">
                            <Label htmlFor="patient-search">
                                Search patient
                            </Label>
                            <Input
                                id="patient-search"
                                value={patientQuery}
                                placeholder="Type a name, number, or phone"
                                onChange={(event) => {
                                    setPatientQuery(event.target.value);
                                    setPatientOpen(true);
                                }}
                            />
                            {patientOpen && patientMatches.length > 0 && (
                                <ul className="absolute top-16 z-10 max-h-64 w-full overflow-auto rounded-md border bg-popover shadow">
                                    {patientMatches.map((patient) => (
                                        <li key={patient.id}>
                                            <button
                                                type="button"
                                                className="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                                onClick={() => {
                                                    form.setData(
                                                        'patient_id',
                                                        String(patient.id),
                                                    );
                                                    setPatientQuery(
                                                        patient.name,
                                                    );
                                                    setPatientOpen(false);
                                                }}
                                            >
                                                <span className="font-medium">
                                                    {patient.name}
                                                </span>
                                                <span className="block text-muted-foreground">
                                                    {patient.patient_number}
                                                    {patient.phone
                                                        ? ` · ${patient.phone}`
                                                        : ''}
                                                    {patient.is_demo
                                                        ? ' · DEMO'
                                                        : ''}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <InputError message={form.errors.patient_id} />
                            {chosen && (
                                <dl className="grid grid-cols-2 gap-2 rounded-lg bg-muted/50 p-3 text-sm">
                                    <dt className="text-muted-foreground">
                                        Name
                                    </dt>
                                    <dd>{chosen.name}</dd>
                                    <dt className="text-muted-foreground">
                                        Number
                                    </dt>
                                    <dd>{chosen.patient_number}</dd>
                                    <dt className="text-muted-foreground">
                                        Sex
                                    </dt>
                                    <dd className="capitalize">{chosen.sex}</dd>
                                    <dt className="text-muted-foreground">
                                        Age
                                    </dt>
                                    <dd>{ageLabel(chosen.date_of_birth)}</dd>
                                    <dt className="text-muted-foreground">
                                        Phone
                                    </dt>
                                    <dd>{chosen.phone || '—'}</dd>
                                </dl>
                            )}
                        </div>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2">
                            <label className="grid gap-1 text-sm">
                                <Label>Name</Label>
                                <Input
                                    value={form.data.patient.name}
                                    onChange={(event) =>
                                        form.setData('patient', {
                                            ...form.data.patient,
                                            name: event.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={form.errors['patient.name']}
                                />
                            </label>
                            <label className="grid gap-1 text-sm">
                                <Label>Sex</Label>
                                <select
                                    className="h-9 rounded-md border bg-background px-2"
                                    value={form.data.patient.sex}
                                    onChange={(event) =>
                                        form.setData('patient', {
                                            ...form.data.patient,
                                            sex: event.target.value,
                                        })
                                    }
                                >
                                    <option value="female">Female</option>
                                    <option value="male">Male</option>
                                    <option value="other">Other</option>
                                </select>
                            </label>
                            <label className="grid gap-1 text-sm">
                                <Label>Date of birth</Label>
                                <Input
                                    type="date"
                                    value={form.data.patient.date_of_birth}
                                    onChange={(event) =>
                                        form.setData('patient', {
                                            ...form.data.patient,
                                            date_of_birth: event.target.value,
                                        })
                                    }
                                />
                            </label>
                            <label className="grid gap-1 text-sm">
                                <Label>Phone</Label>
                                <Input
                                    value={form.data.patient.phone}
                                    onChange={(event) =>
                                        form.setData('patient', {
                                            ...form.data.patient,
                                            phone: event.target.value,
                                        })
                                    }
                                />
                            </label>
                        </div>
                    )}

                    <div className="grid gap-3 md:grid-cols-2">
                        <label className="grid gap-1 text-sm">
                            <Label>Referring doctor</Label>
                            <select
                                className="h-9 rounded-md border bg-background px-2"
                                value={form.data.referring_doctor_id}
                                onChange={(event) =>
                                    form.setData(
                                        'referring_doctor_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">None</option>
                                {doctors.map((doctor) => (
                                    <option key={doctor.id} value={doctor.id}>
                                        {doctor.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-1 text-sm">
                            <Label>Priority</Label>
                            <select
                                className="h-9 rounded-md border bg-background px-2"
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
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="font-medium">2. Tests</h2>
                    <div className="relative grid gap-2">
                        <Label htmlFor="test-search">Test name</Label>
                        <Input
                            id="test-search"
                            value={testQuery}
                            placeholder="Type a test name or code"
                            onChange={(event) =>
                                setTestQuery(event.target.value)
                            }
                        />
                        {testQuery.trim() !== '' && (
                            <ul className="absolute top-16 z-10 max-h-72 w-full overflow-auto rounded-md border bg-popover shadow">
                                {testMatches.length === 0 && (
                                    <li className="px-3 py-2 text-sm text-muted-foreground">
                                        No matching test
                                    </li>
                                )}
                                {testMatches.map((test) => (
                                    <li key={test.id}>
                                        <button
                                            type="button"
                                            className="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                            onClick={() => chooseTest(test)}
                                        >
                                            <span className="font-medium">
                                                {test.name}
                                            </span>
                                            <span className="block text-muted-foreground">
                                                {test.code}
                                                {test.is_panel
                                                    ? ' · panel'
                                                    : ''}
                                                {test.department
                                                    ? ` · ${test.department.name}`
                                                    : ''}
                                                {' · '}
                                                {rupees(test.price_paise)}
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <InputError message={form.errors.test_ids} />
                    </div>
                    {selectedTests.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Selected tests appear here.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {selectedTests.map((test) => (
                                <li
                                    key={test.id}
                                    className="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <span>
                                        <span className="font-medium">
                                            {test.name}
                                        </span>
                                        <span className="block text-muted-foreground">
                                            {test.specimen_type?.name ??
                                                'Specimen'}{' '}
                                            · {rupees(test.price_paise)}
                                        </span>
                                    </span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            form.setData(
                                                'test_ids',
                                                form.data.test_ids.filter(
                                                    (id) => id !== test.id,
                                                ),
                                            )
                                        }
                                    >
                                        Remove
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <Button type="submit" disabled={form.processing}>
                    {canEnterResults
                        ? 'Continue to lab results'
                        : 'Create report'}
                </Button>
            </form>
        </>
    );
}
