import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Camera, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import PasswordInput from '@/components/password-input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { formatDate } from '@/lib/format';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type ProfileDetails = {
    phone: string | null;
    telegram_username: string | null;
    bio: string | null;
    joined_at: string | null;
};

/**
 * The photo field: picks a file, previews it at once, and can remove the
 * current photo. The file itself is sent with the profile form.
 */
function PhotoField({
    name,
    current,
    error,
}: {
    name: string;
    current: string | null;
    error?: string;
}) {
    const getInitials = useInitials();
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [remove, setRemove] = useState(false);
    const shown = remove ? null : (preview ?? current);

    useEffect(
        () => () => (preview ? URL.revokeObjectURL(preview) : undefined),
        [preview],
    );

    return (
        <div className="flex flex-wrap items-center gap-5">
            <Avatar className="size-20 rounded-full">
                {shown ? <AvatarImage src={shown} alt="" /> : null}
                <AvatarFallback className="rounded-full bg-secondary text-2xl text-secondary-foreground">
                    {getInitials(name)}
                </AvatarFallback>
            </Avatar>
            <div className="flex flex-col gap-2">
                <input
                    ref={input}
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) => {
                        const file = event.target.files?.[0];
                        setRemove(false);
                        setPreview(file ? URL.createObjectURL(file) : null);
                    }}
                />
                <input
                    type="hidden"
                    name="remove_avatar"
                    value={remove ? '1' : '0'}
                />
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => input.current?.click()}
                    >
                        <Camera />
                        {current || preview ? 'Change photo' : 'Upload photo'}
                    </Button>
                    {current && !remove ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="text-destructive"
                            onClick={() => {
                                setRemove(true);
                                setPreview(null);
                                if (input.current) {
                                    input.current.value = '';
                                }
                            }}
                        >
                            <Trash2 />
                            Remove
                        </Button>
                    ) : null}
                </div>
                <p className="text-sm text-muted-foreground">
                    A square JPEG, PNG, or WebP up to 1 MB. Save to apply.
                </p>
                <InputError message={error} />
            </div>
        </div>
    );
}

export default function Profile({
    mustVerifyEmail,
    status,
    profile,
    passwordRules,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    profile: ProfileDetails;
    passwordRules: string;
}) {
    const { auth } = usePage().props;
    const user = auth.user;
    const hasPassword = auth.hasPassword === true;

    return (
        <>
            <Head title="Profile" />
            <PageHeader
                title="Profile"
                description={
                    profile.joined_at
                        ? `Your details on Vendly. Member since ${formatDate(profile.joined_at)}.`
                        : 'Your details on Vendly.'
                }
            />

            <Card className="gap-6 p-5 sm:p-6">
                <div>
                    <CardTitle>Your details</CardTitle>
                    <CardDescription className="mt-1">
                        Sellers see your name and contact when you send a
                        request.
                    </CardDescription>
                </div>
                <Form
                    {...ProfileController.update.form()}
                    encType="multipart/form-data"
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['avatar']}
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <PhotoField
                                name={user.name}
                                current={
                                    typeof user.avatar === 'string'
                                        ? user.avatar
                                        : null
                                }
                                error={errors.avatar}
                            />
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        autoComplete="name"
                                        defaultValue={user.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required={!user.telegram_id}
                                        autoComplete="username"
                                        defaultValue={user.email ?? ''}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone number</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        autoComplete="tel"
                                        placeholder="+855 12 345 678"
                                        defaultValue={profile.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="telegram_username">
                                        Telegram username
                                    </Label>
                                    <Input
                                        id="telegram_username"
                                        name="telegram_username"
                                        placeholder="@yourname"
                                        defaultValue={
                                            profile.telegram_username
                                                ? `@${profile.telegram_username}`
                                                : ''
                                        }
                                    />
                                    <InputError
                                        message={errors.telegram_username}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="bio">About you</Label>
                                <Textarea
                                    id="bio"
                                    name="bio"
                                    rows={3}
                                    maxLength={500}
                                    placeholder="A line about you or what you sell."
                                    defaultValue={profile.bio ?? ''}
                                />
                                <InputError message={errors.bio} />
                            </div>

                            {mustVerifyEmail &&
                            user.email &&
                            user.email_verified_at === null ? (
                                <p className="rounded-xl bg-warning/10 p-3 text-sm text-warning">
                                    Your email address is not verified.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="font-medium underline underline-offset-4"
                                    >
                                        Send the verification email again.
                                    </Link>
                                    {status === 'verification-link-sent'
                                        ? ' A new link is on its way.'
                                        : ''}
                                </p>
                            ) : null}

                            <Button
                                type="submit"
                                disabled={processing}
                                className="justify-self-start"
                                data-test="update-profile-button"
                            >
                                {processing && <Spinner />}
                                Save profile
                            </Button>
                        </>
                    )}
                </Form>
            </Card>

            <Card className="gap-6 p-5 sm:p-6">
                <div>
                    <CardTitle>
                        {hasPassword ? 'Password' : 'Set a password'}
                    </CardTitle>
                    <CardDescription className="mt-1">
                        {hasPassword
                            ? 'Use a long password you do not use anywhere else.'
                            : 'You signed in with Google or Telegram. Set a password to also log in with your email.'}
                    </CardDescription>
                </div>
                <Form
                    {...SecurityController.update.form()}
                    options={{ preserveScroll: true }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    className="grid gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            {hasPassword ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">
                                        Current password
                                    </Label>
                                    <PasswordInput
                                        id="current_password"
                                        name="current_password"
                                        autoComplete="current-password"
                                    />
                                    <InputError
                                        message={errors.current_password}
                                    />
                                </div>
                            ) : null}
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        New password
                                    </Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoComplete="new-password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm new password
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                                className="justify-self-start"
                                data-test="update-password-button"
                            >
                                {processing && <Spinner />}
                                {hasPassword
                                    ? 'Change password'
                                    : 'Set password'}
                            </Button>
                        </>
                    )}
                </Form>
            </Card>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [{ title: 'Profile', href: edit() }],
};
