import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type OrderRow = {
    id: number;
    order_number: string;
    status: string;
    priority: string;
    patient: { name: string; patient_number: string; is_demo: boolean };
    items: { test: { name: string } }[];
};

export default function OrdersIndex({ orders }: { orders: OrderRow[] }) {
    return (
        <>
            <Head title="Orders" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Orders</h1>
                    <Button asChild>
                        <Link href="/orders/create">New order</Link>
                    </Button>
                </div>
                <div className="overflow-hidden rounded-xl border">
                    {orders.map((order) => (
                        <Link
                            key={order.id}
                            href={`/orders/${order.id}`}
                            className="grid grid-cols-4 gap-2 border-b p-3 text-sm last:border-b-0"
                        >
                            <span className="font-medium">
                                {order.order_number}
                            </span>
                            <span>
                                {order.patient.name}
                                {order.patient.is_demo ? ' · DEMO' : ''}
                            </span>
                            <span>
                                {order.items
                                    .map((item) => item.test.name)
                                    .join(', ')}
                            </span>
                            <span className="text-right">
                                {order.priority} · {order.status}
                            </span>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
