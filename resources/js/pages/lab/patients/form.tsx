import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PatientForm() {
    const form = useForm({
        name: '',
        sex: 'female',
        date_of_birth: '',
        phone: '',
        email: '',
        address: '',
        is_demo: false,
    });

    return (
        <>
            <Head title="Register patient" />
            <form
                className="mx-auto flex max-w-xl flex-col gap-4 p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/patients');
                }}
            >
                <h1 className="text-2xl font-semibold">Register patient</h1>
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="sex">Sex</Label>
                    <select
                        id="sex"
                        className="h-9 rounded-md border px-2"
                        value={form.data.sex}
                        onChange={(event) =>
                            form.setData('sex', event.target.value)
                        }
                    >
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="dob">Date of birth</Label>
                    <Input
                        id="dob"
                        type="date"
                        value={form.data.date_of_birth}
                        onChange={(event) =>
                            form.setData('date_of_birth', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone</Label>
                    <Input
                        id="phone"
                        value={form.data.phone}
                        onChange={(event) =>
                            form.setData('phone', event.target.value)
                        }
                    />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.is_demo}
                        onChange={(event) =>
                            form.setData('is_demo', event.target.checked)
                        }
                    />
                    Mark as demonstration record. Reports stay watermarked and
                    cannot be delivered.
                </label>
                <Button type="submit" disabled={form.processing}>
                    Save patient
                </Button>
            </form>
        </>
    );
}
