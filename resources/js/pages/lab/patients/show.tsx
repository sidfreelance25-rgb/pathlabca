import { Head, Link } from '@inertiajs/react';

type PatientShowProps = {
    patient: {
        id: number;
        patient_number: string;
        name: string;
        sex: string;
        phone: string | null;
        is_demo: boolean;
        date_of_birth: string | null;
        orders: {
            id: number;
            order_number: string;
            status: string;
            priority: string;
        }[];
    };
};

export default function PatientShow({ patient }: PatientShowProps) {
    return (
        <>
            <Head title={patient.name} />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">{patient.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {patient.patient_number} · {patient.sex}
                        {patient.is_demo ? ' · DEMONSTRATION RECORD' : ''}
                    </p>
                </div>
                <ButtonLink href={`/orders/create?patient=${patient.id}`}>
                    New order
                </ButtonLink>
                <div className="rounded-xl border">
                    {patient.orders.map((order) => (
                        <Link
                            key={order.id}
                            href={`/orders/${order.id}`}
                            className="flex justify-between border-b p-3 text-sm last:border-b-0"
                        >
                            <span>{order.order_number}</span>
                            <span>
                                {order.status} · {order.priority}
                            </span>
                        </Link>
                    ))}
                    {patient.orders.length === 0 && (
                        <p className="p-3 text-sm">No orders yet.</p>
                    )}
                </div>
            </div>
        </>
    );
}

function ButtonLink({ href, children }: { href: string; children: string }) {
    return (
        <Link
            href={href}
            className="inline-flex h-9 w-fit items-center rounded-md bg-primary px-4 text-sm text-primary-foreground"
        >
            {children}
        </Link>
    );
}
