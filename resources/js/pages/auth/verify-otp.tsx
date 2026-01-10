import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import InputError from '@/components/input-error';
import OtpCodeController from '@/actions/App/Http/Controllers/Auth/OtpCodeController';

type VerifyOtpProps = {
    status?: string;
};

export default function VerifyOtp({ status }: VerifyOtpProps): JSX.Element {
    return (
        <AuthLayout title="Vérification du compte" description="Entrez le code à 6 chiffres reçu sur WhatsApp pour activer votre compte.">
            <Head title="OTP verification" />

            {status === 'otp-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">Un nouveau code de vérification a été envoyé sur WhatsApp.</div>
            )}

            <Form {...OtpCodeController.verify} className="space-y-6 grid text-center">
                {({ processing, errors,  }) => (
                    <>
                        {/* OTP INPUT */}
                        <div className="flex justify-center">
                            <InputOTP maxLength={6} >
                                <InputOTPGroup>
                                    {[...Array(6)].map((_, index) => (
                                        <InputOTPSlot key={index} index={index} />
                                    ))}
                                </InputOTPGroup>
                            </InputOTP>
                        </div>

                        {/* ERROR */}
                        <InputError message={errors.code} />


                        {/* SUBMIT */}
                        <Button type="submit"  disabled={processing}>
                            {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                            Vérifier le code
                        </Button>

                        {/* RESEND */}
                        <Form {...OtpCodeController.resend} className="text-center w-full grid ">
                            {({ processing: resendProcessing }) => (
                                <Button type="submit" variant="secondary" disabled={resendProcessing}>
                                    {resendProcessing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                                    Renvoyer le code
                                </Button>
                            )}
                        </Form>

                        {/* LOGOUT */}
                        <TextLink href={logout()} className="mx-auto block text-sm">
                            Se déconnecter
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
