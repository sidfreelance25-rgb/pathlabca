import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function SpecimenLabel({
    specimen,
    barcode,
}: {
    specimen: {
        barcode: string;
        specimen_type: { name: string; container: string | null };
        order: {
            order_number: string;
            patient: {
                name: string;
                patient_number: string;
                sex: string;
                is_demo: boolean;
            };
            branch: { lab: { name: string } };
        };
    };
    barcode: string;
}) {
    return (
        <>
            <Head title={`Label ${specimen.barcode}`} />
            <div className="p-4">
                <Button
                    className="mb-4 print:hidden"
                    onClick={() => window.print()}
                >
                    Print label
                </Button>
                <article className="w-[340px] rounded-xl border p-4">
                    <div className="text-xs tracking-wide uppercase">
                        {specimen.order.branch.lab.name}
                    </div>
                    <div className="mt-1 text-lg font-semibold">
                        {specimen.order.patient.name}
                    </div>
                    <div className="text-sm">
                        {specimen.order.patient.patient_number} ·{' '}
                        {specimen.order.patient.sex}
                        {specimen.order.patient.is_demo ? ' · DEMO' : ''}
                    </div>
                    <div className="mt-2 text-sm">
                        {specimen.specimen_type.name}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {specimen.specimen_type.container}
                    </div>
                    <img
                        src={barcode}
                        alt={specimen.barcode}
                        className="mt-3 h-14"
                    />
                    <div className="font-mono text-sm">{specimen.barcode}</div>
                    <div className="text-xs text-muted-foreground">
                        {specimen.order.order_number}
                    </div>
                </article>
            </div>
        </>
    );
}
