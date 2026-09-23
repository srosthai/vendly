import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useEffect, useState } from 'react';
import GoogleAuthController from '@/actions/App/Http/Controllers/Auth/GoogleAuthController';
import RegisterController from '@/actions/App/Http/Controllers/Auth/RegisterController';
import { GoogleMark } from '@/components/auth/google-mark';
import { OrSeparator } from '@/components/auth/or-separator';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login, register } from '@/routes';

type Props = {
    passwordRules: string;
    step: 'details' | 'code';
    email: string;
    status?: string;
};

const resendWait = 60;

/**
 * A new code can be asked for once a minute. The countdown restarts after
 * each send, so the button never promises something the server refuses.
 */
function ResendCode() {
    const [secondsLeft, setSecondsLeft] = useState(resendWait);

    useEffect(() => {
        if (secondsLeft <= 0) {
            return;
        }

        const timer = window.setTimeout(
            () => setSecondsLeft((seconds) => seconds - 1),
            1000,
        );

        return () => window.clearTimeout(timer);
    }, [secondsLeft]);

    return (
        <div className="flex flex-col items-center gap-2 text-sm text-muted-foreground">
            <Form
                {...RegisterController.resend.form()}
                options={{ preserveScroll: true }}
                onSuccess={() => setSecondsLeft(resendWait)}
            >
                {({ processing }) => (
                    <Button
                        type="submit"
                        variant="link"
                        disabled={processing || secondsLeft > 0}
                        className="h-auto p-0"
                    >
                        {secondsLeft > 0
                            ? `Send a new code in ${secondsLeft}s`
                            : 'Send a new code'}
                    </Button>
                )}
            </Form>
            <TextLink href={register()}>Use a different email</TextLink>
        </div>
    );
}

export default function Register({
    passwordRules,
    step,
    email,
    status,
}: Props) {
    const [code, setCode] = useState('');

    return (
        <>
            <Head title="Register" />
            {status ? (
                <p
                    role="status"
                    className="rounded-xl bg-success/10 px-4 py-3 text-center text-sm font-medium text-success"
                >
                    {status}
                </p>
            ) : null}
            {step === 'details' ? (
                <>
                    <Button variant="outline" className="w-full" asChild>
                        <a href={GoogleAuthController.redirect.url()}>
                            <GoogleMark />
                            Continue with Google
                        </a>
                    </Button>
                    <OrSeparator label="or use your email" />
                    <Form
                        {...RegisterController.store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="flex flex-col gap-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        autoFocus
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>
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
                                    <Label htmlFor="password">Password</Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        autoComplete="new-password"
                                        placeholder="Password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                    data-test="register-user-button"
                                >
                                    {processing && <Spinner />}
                                    Email me a code
                                </Button>
                                <p className="text-center text-sm text-muted-foreground">
                                    Already have an account?{' '}
                                    <TextLink href={login()}>Log in</TextLink>
                                </p>
                            </>
                        )}
                    </Form>
                </>
            ) : (
                <Form
                    {...RegisterController.verify.form()}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="code" value={code} />
                            <p className="text-center text-sm text-muted-foreground">
                                Enter the six-digit code we sent to{' '}
                                <span className="font-medium text-foreground">
                                    {email}
                                </span>
                                .
                            </p>
                            <div className="flex justify-center">
                                <InputOTP
                                    maxLength={6}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    value={code}
                                    onChange={setCode}
                                    autoFocus
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot index={0} />
                                        <InputOTPSlot index={1} />
                                        <InputOTPSlot index={2} />
                                        <InputOTPSlot index={3} />
                                        <InputOTPSlot index={4} />
                                        <InputOTPSlot index={5} />
                                    </InputOTPGroup>
                                </InputOTP>
                            </div>
                            <InputError
                                message={errors.code ?? errors.email}
                                className="text-center"
                            />
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing || code.length !== 6}
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </>
                    )}
                </Form>
            )}
            {step === 'code' ? <ResendCode /> : null}
        </>
    );
}

Register.layout = {
    title: 'Create your Vendly account',
    description: 'We email you a code to check the address before the account is made.',
};
