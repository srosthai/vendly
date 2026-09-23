import { Form, Head } from '@inertiajs/react';
import GoogleAuthController from '@/actions/App/Http/Controllers/Auth/GoogleAuthController';
import { GoogleMark } from '@/components/auth/google-mark';
import { OrSeparator } from '@/components/auth/or-separator';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    googleSignIn?: boolean;
};

export default function Login({
    status,
    canResetPassword,
    googleSignIn = false,
}: Props) {
    return (
        <>
            <Head title="Log in" />

            {status ? (
                <p
                    role="status"
                    className="rounded-xl bg-success/10 px-4 py-3 text-center text-sm font-medium text-success"
                >
                    {status}
                </p>
            ) : null}

            {googleSignIn ? (
                <>
                    <Button variant="outline" className="w-full" asChild>
                        <a href={GoogleAuthController.redirect.url()}>
                            <GoogleMark />
                            Continue with Google
                        </a>
                    </Button>
                    <OrSeparator label="or use your email" />
                </>
            ) : null}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                        >
                                            Forgot password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox id="remember" name="remember" />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        <p className="text-center text-sm text-muted-foreground">
                            New to Vendly?{' '}
                            <TextLink href={register()}>
                                Create an account
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Log in to Vendly',
    description: 'Manage your store, or send the products you picked.',
};
