import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Definition = {
    id: number;
    label: string;
    unit: string | null;
    data_type: string;
    allowed_values: string[] | null;
    reference_intervals: {
        sex: string;
        lower_bound: string | null;
        upper_bound: string | null;
        unit: string | null;
    }[];
};

type Item = {
    id: number;
    test: {
        name: string;
        is_panel: boolean;
        result_definitions: Definition[];
        components: { name: string; result_definitions: Definition[] }[];
    };
    results: {
        result_definition_id: number;
        value: string | null;
        comment: string | null;
        flag: string | null;
    }[];
};

export default function OrderResults({
    order,
}: {
    order: {
        id: number;
        order_number: string;
        patient: { name: string; is_demo: boolean };
        items: Item[];
    };
}) {
    const existing: Record<number, { value: string; comment: string }> = {};
    order.items.forEach((item) => {
        item.results.forEach((result) => {
            existing[result.result_definition_id] = {
                value: result.value ?? '',
                comment: result.comment ?? '',
            };
        });
    });
    const form = useForm<{
        results: Record<number, { value: string; comment: string }>;
    }>({
        results: existing,
    });

    const sections = order.items.map((item) => ({
        name: item.test.name,
        definitions: item.test.is_panel
            ? item.test.components.flatMap(
                  (component) => component.result_definitions,
              )
            : item.test.result_definitions,
    }));

    return (
        <>
            <Head title={`Results ${order.order_number}`} />
            <form
                className="flex flex-col gap-4 p-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(`/orders/${order.id}/results`);
                }}
            >
                <div>
                    <h1 className="text-2xl font-semibold">
                        Results · {order.order_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {order.patient.name}
                        {order.patient.is_demo
                            ? ' · demonstration values are flagged DEMO and cannot be delivered as a clinical report'
                            : ''}
                    </p>
                </div>
                {sections.map((section) => (
                    <section key={section.name} className="rounded-xl border">
                        <h2 className="border-b p-3 font-medium">
                            {section.name}
                        </h2>
                        {section.definitions.map((definition) => {
                            const current = form.data.results[
                                definition.id
                            ] ?? { value: '', comment: '' };
                            return (
                                <div
                                    key={definition.id}
                                    className="grid gap-2 border-b p-3 md:grid-cols-4"
                                >
                                    <div>
                                        <div className="font-medium">
                                            {definition.label}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {definition.reference_intervals.map(
                                                (interval) => (
                                                    <span
                                                        key={interval.sex}
                                                        className="mr-2"
                                                    >
                                                        {interval.sex}{' '}
                                                        {interval.lower_bound ??
                                                            ''}
                                                        –
                                                        {interval.upper_bound ??
                                                            ''}{' '}
                                                        {interval.unit}
                                                    </span>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                    {definition.allowed_values ? (
                                        <select
                                            className="h-9 rounded-md border px-2"
                                            value={current.value}
                                            onChange={(event) =>
                                                form.setData('results', {
                                                    ...form.data.results,
                                                    [definition.id]: {
                                                        ...current,
                                                        value: event.target
                                                            .value,
                                                    },
                                                })
                                            }
                                        >
                                            <option value="">Select</option>
                                            {definition.allowed_values.map(
                                                (option) => (
                                                    <option key={option}>
                                                        {option}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    ) : (
                                        <Input
                                            value={current.value}
                                            onChange={(event) =>
                                                form.setData('results', {
                                                    ...form.data.results,
                                                    [definition.id]: {
                                                        ...current,
                                                        value: event.target
                                                            .value,
                                                    },
                                                })
                                            }
                                        />
                                    )}
                                    <div className="text-sm text-muted-foreground">
                                        {definition.unit}
                                    </div>
                                    <Input
                                        placeholder="Comment"
                                        value={current.comment}
                                        onChange={(event) =>
                                            form.setData('results', {
                                                ...form.data.results,
                                                [definition.id]: {
                                                    ...current,
                                                    comment: event.target.value,
                                                },
                                            })
                                        }
                                    />
                                </div>
                            );
                        })}
                    </section>
                ))}
                <Button type="submit" disabled={form.processing}>
                    Save results
                </Button>
            </form>
        </>
    );
}
