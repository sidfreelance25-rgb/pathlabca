import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export default function TemplatesIndex({
    templates,
    departments,
}: {
    templates: {
        id: number;
        name: string;
        header_style: string;
        status: string;
        version: number;
        department: { name: string } | null;
    }[];
    departments: { id: number; name: string }[];
}) {
    const form = useForm({
        name: '',
        header_style: 'branded_left',
        department_id: '',
    });

    return (
        <>
            <Head title="Report templates" />
            <div className="grid gap-4 p-4 lg:grid-cols-2">
                <section>
                    <h1 className="mb-3 text-2xl font-semibold">
                        Approved templates
                    </h1>
                    <div className="rounded-xl border">
                        {templates.map((template) => (
                            <div
                                key={template.id}
                                className="border-b p-3 text-sm last:border-b-0"
                            >
                                <div className="font-medium">
                                    {template.name}
                                </div>
                                <div className="text-muted-foreground">
                                    {template.header_style} · v
                                    {template.version} · {template.status}
                                    {template.department
                                        ? ` · ${template.department.name}`
                                        : ' · branch default'}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
                <form
                    className="flex flex-col gap-3 rounded-xl border p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/templates');
                    }}
                >
                    <h2 className="font-medium">Approve another header</h2>
                    <Input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder="Template name"
                    />
                    <select
                        className="h-9 rounded-md border px-2"
                        value={form.data.header_style}
                        onChange={(event) =>
                            form.setData('header_style', event.target.value)
                        }
                    >
                        <option value="branded_left">
                            Logo left, details right
                        </option>
                        <option value="centered">Centred</option>
                        <option value="compact">Compact</option>
                    </select>
                    <select
                        className="h-9 rounded-md border px-2"
                        value={form.data.department_id}
                        onChange={(event) =>
                            form.setData('department_id', event.target.value)
                        }
                    >
                        <option value="">Branch default style</option>
                        {departments.map((department) => (
                            <option key={department.id} value={department.id}>
                                {department.name}
                            </option>
                        ))}
                    </select>
                    <Button type="submit">Approve template</Button>
                </form>
            </div>
        </>
    );
}
