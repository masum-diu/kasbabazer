import { Head, router } from '@inertiajs/react';
import { Copy } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPrice } from '@/lib/market';

type Props = {
    plan: { slug: string; name: string; price: number; listing_limit: number | null };
    pay: { bkash: string; nagad: string | null; days: number };
};

export default function Checkout({ plan, pay }: Props) {
    const [method, setMethod] = useState<'bkash' | 'nagad'>('bkash');
    const [sender, setSender] = useState('');
    const [trx, setTrx] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const number = method === 'bkash' ? pay.bkash : pay.nagad;

    const copy = async () => {
        if (!number) {
            return;
        }

        try {
            await navigator.clipboard.writeText(number);
            toast.success('Number copied.');
        } catch {
            toast.error('Could not copy. Please type the number.');
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/plans/${plan.slug}/checkout`, { payment_method: method, sender_number: sender, transaction_id: trx }, {
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onError: setErrors,
        });
    };

    return (
        <>
            <Head title={`Subscribe to ${plan.name}`} />
            <div>
                <h1 className="text-2xl font-bold">Subscribe to {plan.name}</h1>
                <p className="mt-1 text-muted-foreground">
                    {formatPrice(plan.price)} for {pay.days} days.
                </p>

                <ol className="mt-6 grid gap-6 rounded-2xl border bg-card p-5 text-sm lg:grid-cols-3 lg:p-6">
                    <li>
                        <p className="font-medium">1. Choose how you will pay</p>
                        <div className="mt-2 flex gap-2" role="radiogroup" aria-label="Payment method">
                            {(['bkash', 'nagad'] as const).map((m) => (
                                <button
                                    key={m}
                                    type="button"
                                    role="radio"
                                    aria-checked={method === m}
                                    disabled={m === 'nagad' && !pay.nagad}
                                    onClick={() => setMethod(m)}
                                    className={`rounded-full border px-4 py-1.5 capitalize disabled:opacity-40 ${method === m ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-accent'}`}
                                >
                                    {m === 'bkash' ? 'bKash' : 'Nagad'}
                                </button>
                            ))}
                        </div>
                    </li>
                    <li>
                        <p className="font-medium">
                            2. Send Money <strong>{formatPrice(plan.price)}</strong> to
                        </p>
                        <div className="mt-2 flex items-center justify-between gap-2 rounded-lg bg-muted p-3">
                            <span className="text-lg font-bold tracking-wider">{number ?? 'Not available'}</span>
                            <Button type="button" size="sm" variant="outline" onClick={copy} className="gap-1"><Copy className="size-3" /> Copy</Button>
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">Use “Send Money” and write your name in the reference.</p>
                    </li>
                    <li>
                        <p className="mb-2 font-medium">3. Enter your payment details</p>
                        <form onSubmit={submit} className="flex flex-col gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="sender">Number you paid from</Label>
                                <Input id="sender" inputMode="tel" value={sender} onChange={(e) => setSender(e.target.value)} placeholder="01XXXXXXXXX" required />
                                <InputError message={errors.sender_number} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="trx">Transaction ID (TrxID)</Label>
                                <Input id="trx" value={trx} onChange={(e) => setTrx(e.target.value.toUpperCase())} placeholder="e.g. 9H7A5K2LMN" required />
                                <InputError message={errors.transaction_id ?? errors.plan ?? errors.payment_method} />
                            </div>
                            <Button type="submit" disabled={busy}>Submit payment</Button>
                        </form>
                    </li>
                </ol>
                <p className="mt-3 text-xs text-muted-foreground">Your plan starts after an admin confirms the payment, usually within a few hours.</p>
            </div>
        </>
    );
}
