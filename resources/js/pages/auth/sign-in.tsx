import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type SignInProps = {
    step: 'email' | 'code';
    email: string;
    status?: string;
};

export default function SignIn({ step, email, status }: SignInProps) {
    const [code, setCode] = useState('');

    return (
        <>
            <Head title="Sign in" />
            {status ? (
                <p className="text-center text-sm text-muted-foreground">
                    {status}
                </p>
            ) : null}
            {step === 'email' ? (
                <Form
                    action="/auth/email-code"
                    method="post"
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder="you@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Email me a code
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <Form
                    action="/auth/email-code/verify"
                    method="post"
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="email" value={email} />
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
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    autoComplete="name"
                                    placeholder="Your name"
                                />
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing || code.length !== 6}
                            >
                                {processing && <Spinner />}
                                Sign in
                            </Button>
                        </>
                    )}
                </Form>
            )}
            <Button variant="outline" className="w-full" asChild>
                <a href="/auth/google/redirect">Continue with Google</a>
            </Button>
        </>
    );
}

SignIn.layout = {
    title: 'Sign in',
    description: 'Use Google, or an email code. No password.',
};
