import { Head, router, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Account = {
    display_number: string;
    phone_number_id: string;
    verified_name: string | null;
    template_name: string | null;
    connected_at: string | null;
};

export default function WhatsappSettings({
    account,
}: {
    account: Account | null;
}) {
    const form = useForm({
        phone_number_id: account?.phone_number_id ?? '',
        access_token: '',
        template_name: account?.template_name ?? '',
    });

    return (
        <>
            <Head title="WhatsApp" />
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="WhatsApp"
                    description="Each technician sends reports from their own WhatsApp Business number through Meta's official Cloud API. A QR scan of the WhatsApp Web app is not used, because WhatsApp bans numbers that connect that way."
                />
                {account && (
                    <div className="rounded-xl border p-4 text-sm">
                        <p className="font-medium">
                            Connected as{' '}
                            {account.verified_name || account.display_number}
                        </p>
                        <p className="text-muted-foreground">
                            {account.display_number}
                            {account.template_name
                                ? ` · template ${account.template_name}`
                                : ' · session document, only after the patient has messaged this number in the last 24 hours'}
                        </p>
                        <Button
                            className="mt-3"
                            type="button"
                            variant="outline"
                            onClick={() => router.delete('/settings/whatsapp')}
                        >
                            Remove this number
                        </Button>
                    </div>
                )}
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put('/settings/whatsapp');
                    }}
                >
                    <label className="grid gap-1 text-sm">
                        <Label>Phone number ID</Label>
                        <Input
                            value={form.data.phone_number_id}
                            onChange={(event) =>
                                form.setData(
                                    'phone_number_id',
                                    event.target.value,
                                )
                            }
                            placeholder="From Meta, not the phone number itself"
                        />
                        <InputError message={form.errors.phone_number_id} />
                    </label>
                    <label className="grid gap-1 text-sm">
                        <Label>Access token</Label>
                        <Input
                            type="password"
                            value={form.data.access_token}
                            onChange={(event) =>
                                form.setData('access_token', event.target.value)
                            }
                            placeholder="Permanent token from Meta Business"
                        />
                        <InputError message={form.errors.access_token} />
                    </label>
                    <label className="grid gap-1 text-sm">
                        <Label>Approved template name</Label>
                        <Input
                            value={form.data.template_name}
                            onChange={(event) =>
                                form.setData(
                                    'template_name',
                                    event.target.value,
                                )
                            }
                            placeholder="lab_report"
                        />
                        <p className="text-muted-foreground">
                            Use a utility template whose header is a document.
                            Leave this empty only when the patient already
                            messaged this number today.
                        </p>
                        <InputError message={form.errors.template_name} />
                    </label>
                    <Button type="submit" disabled={form.processing}>
                        {account ? 'Update WhatsApp' : 'Connect WhatsApp'}
                    </Button>
                </form>
                <ol className="list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
                    <li>Create a Meta app and add the WhatsApp product.</li>
                    <li>
                        Add this technician's business number and verify it by
                        SMS or a voice call.
                    </li>
                    <li>
                        Copy the phone number ID and a permanent access token
                        into the form above.
                    </li>
                    <li>
                        Approve a document-header template if the lab starts the
                        conversation.
                    </li>
                </ol>
            </div>
        </>
    );
}

WhatsappSettings.layout = {
    breadcrumbs: [{ title: 'WhatsApp', href: '/settings/whatsapp' }],
};
