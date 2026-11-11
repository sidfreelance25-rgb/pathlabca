import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import AppearanceToggleTab from '@/components/appearance-tabs';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
    auth: Auth;
};

type LetterheadLab = {
    name: string;
    address: string;
    phone: string;
    email: string;
    doctor: string;
    qual: string;
    tagline: string;
    iso: string;
};

export default function Profile({
    mustVerifyEmail,
    status,
    letterhead,
    letterheadMissing = [],
    letterheadSaved = false,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    letterhead?: { lab: LetterheadLab };
    letterheadMissing?: string[];
    letterheadSaved?: boolean;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-3 rounded-xl border p-4">
                <Heading
                    variant="small"
                    title="Theme"
                    description="Light and dark apply across the whole app. The same switch is in the side navigation."
                />
                <AppearanceToggleTab />
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <div className="space-y-3 rounded-xl border p-4">
                <Heading
                    variant="small"
                    title="Laboratory letterhead"
                    description="Lab name, doctor and header design are saved on your account and used on reports you release."
                />
                <dl className="grid grid-cols-2 gap-2 text-sm">
                    <dt className="text-muted-foreground">Lab name</dt>
                    <dd>{letterhead?.lab.name || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Address</dt>
                    <dd>{letterhead?.lab.address || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Phone</dt>
                    <dd>{letterhead?.lab.phone || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Email</dt>
                    <dd>{letterhead?.lab.email || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Doctor</dt>
                    <dd>{letterhead?.lab.doctor || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Qualification</dt>
                    <dd>{letterhead?.lab.qual || 'Missing'}</dd>
                    <dt className="text-muted-foreground">Certification</dt>
                    <dd>{letterhead?.lab.iso || '—'}</dd>
                    <dt className="text-muted-foreground">Tagline</dt>
                    <dd>{letterhead?.lab.tagline || '—'}</dd>
                </dl>
                {letterheadMissing.length > 0 ? (
                    <p className="text-sm text-amber-700">
                        Still needed: {letterheadMissing.join(', ')}.
                        {letterheadSaved
                            ? ''
                            : ' Save the letterhead to keep them on your account.'}
                    </p>
                ) : (
                    <p className="text-sm text-green-700">
                        Lab name, contact details and doctor are filled in.
                    </p>
                )}
                <Button asChild variant="outline">
                    <Link href="/settings/letterhead">
                        Edit letterhead and preview
                    </Link>
                </Button>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
