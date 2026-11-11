import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Interval = {
    sex: string;
    lower_bound: string | null;
    upper_bound: string | null;
    unit: string | null;
    source: string;
    population_note: string | null;
};

type ResultDef = {
    id: number;
    label: string;
    loinc: string | null;
    unit: string | null;
    data_type: string;
    reference_intervals: Interval[];
    critical_limits: {
        lower_bound: string | null;
        upper_bound: string | null;
        source: string;
        note: string | null;
    }[];
};

type TestNode = {
    id: number;
    code: string;
    name: string;
    method: string | null;
    collection_notes: string | null;
    is_panel: boolean;
    specimen_type: {
        name: string;
        container: string | null;
        handling_instructions: string | null;
    };
    department: { name: string };
    components: {
        id: number;
        name: string;
        code: string;
        result_definitions: ResultDef[];
    }[];
    result_definitions: ResultDef[];
};

export default function CatalogueShow({
    test,
    scope,
    ownerName,
    canPromote,
}: {
    test: TestNode;
    scope: 'main' | 'copy' | 'personal';
    ownerName?: string | null;
    canPromote: boolean;
}) {
    const scopeText =
        scope === 'main'
            ? 'Main test'
            : scope === 'copy'
              ? 'Your copy of a main test'
              : 'Personal test';
    const rows = test.is_panel
        ? test.components.flatMap((component) =>
              component.result_definitions.map((result) => ({
                  ...result,
                  group: component.name,
              })),
          )
        : test.result_definitions.map((result) => ({
              ...result,
              group: test.name,
          }));

    return (
        <>
            <Head title={test.name} />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold">{test.name}</h1>
                        <span className="rounded-full border px-2 py-0.5 text-xs">
                            {scopeText}
                        </span>
                        {canPromote && (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    router.post(`/catalogue/${test.id}/promote`)
                                }
                            >
                                Promote to main
                            </Button>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {test.code} · {test.department.name} ·{' '}
                        {test.specimen_type.name}
                        {test.specimen_type.container
                            ? ` · ${test.specimen_type.container}`
                            : ''}
                    </p>
                    {scope === 'personal' && ownerName && (
                        <p className="text-sm text-muted-foreground">
                            Owned by {ownerName}. Promoting it makes this the
                            shared test.
                        </p>
                    )}
                    {scope === 'main' && (
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            This test is shared. Staff who have not saved their
                            own copy use this version.
                        </p>
                    )}
                    {test.collection_notes && (
                        <p className="mt-2 max-w-3xl text-sm">
                            {test.collection_notes}
                        </p>
                    )}
                    {test.specimen_type.handling_instructions && (
                        <p className="text-sm text-muted-foreground">
                            {test.specimen_type.handling_instructions}
                        </p>
                    )}
                </div>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Component</th>
                                <th className="p-3">LOINC</th>
                                <th className="p-3">Interval</th>
                                <th className="p-3">Critical</th>
                                <th className="p-3">Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.id} className="border-t align-top">
                                    <td className="p-3">
                                        <div className="font-medium">
                                            {row.label}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {row.group} ·{' '}
                                            {row.unit ?? row.data_type}
                                        </div>
                                    </td>
                                    <td className="p-3">{row.loinc ?? '—'}</td>
                                    <td className="p-3">
                                        {row.reference_intervals.map(
                                            (interval) => (
                                                <div
                                                    key={`${interval.sex}-${interval.lower_bound}-${interval.upper_bound}`}
                                                >
                                                    {interval.sex}:{' '}
                                                    {interval.lower_bound ??
                                                        '—'}{' '}
                                                    –{' '}
                                                    {interval.upper_bound ??
                                                        '—'}{' '}
                                                    {interval.unit}
                                                </div>
                                            ),
                                        )}
                                        {row.reference_intervals.length === 0 &&
                                            'No numeric interval seeded'}
                                    </td>
                                    <td className="p-3">
                                        {row.critical_limits.map((limit) => (
                                            <div key={limit.source}>
                                                {limit.lower_bound ?? '—'} /{' '}
                                                {limit.upper_bound ?? '—'}
                                            </div>
                                        ))}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {row.reference_intervals[0]?.source ??
                                            row.critical_limits[0]?.note}
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
