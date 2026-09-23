import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
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
import { login } from '@/routes';

type Props = {
    passwordRules: string;
    step: 'details' | 'code';
    email: string;
    status?: string;
};

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
                <p className="text-center text-sm text-muted-foreground">
                    {status}
                </p>
            ) : null}
            {step === 'details' ? (
                <>
                    <Button variant="outline" className="w-full" asChild>
                        <a href="/auth/google/redirect">Continue with Google</a>
                    </Button>
                    <Form
                        action="/register/code"
                        method="post"
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
                                <div className="text-center text-sm text-muted-foreground">
                                    Already have an account?{' '}
                                    <TextLink href={login()}>Log in</TextLink>
                                </div>
                            </>
                        )}
                    </Form>
                </>
            ) : (
                <Form
                    action="/register/code/verify"
                    method="post"
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="code" value={code} />
                            <p className="text-center text-sm text-muted-foreground">
                                Code sent to {email}
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
                            <InputError message={errors.code} />
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
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'We email a code before the account is created.',
};
