import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    profile: { shop_name: string; description: string | null; phone: string | null; address: string | null; location_id: number | null } | null;
    locations: { id: number; name: string }[];
};

export default function SellerApply({ profile, locations }: Props) {
    const [values, setValues] = useState({
        shop_name: profile?.shop_name ?? '',
        description: profile?.description ?? '',
        phone: profile?.phone ?? '',
        address: profile?.address ?? '',
        location_id: profile?.location_id ? String(profile.location_id) : '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const set = (key: keyof typeof values, value: string) => setValues((v) => ({ ...v, [key]: value }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/seller/apply', values, {
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onError: setErrors,
        });
    };

    return (
        <>
            <Head title={profile ? 'Shop profile' : 'Become a seller'} />
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-bold">{profile ? 'Your shop profile' : 'Become a seller'}</h1>
                    <p className="text-sm text-muted-foreground">You do not need a new account. Tell buyers about your shop and start listing.</p>
                </div>

                <div className="grid gap-4 rounded-xl border bg-card p-4 md:grid-cols-2 md:p-6">
                <div className="grid gap-2">
                    <Label htmlFor="shop_name">Shop name</Label>
                    <Input id="shop_name" value={values.shop_name} onChange={(e) => set('shop_name', e.target.value)} required maxLength={100} />
                    <InputError message={errors.shop_name} />
                </div>
                <div className="contents">
                    <div className="grid gap-2">
                        <Label htmlFor="phone">Shop phone</Label>
                        <Input id="phone" value={values.phone} onChange={(e) => set('phone', e.target.value)} placeholder="01XXXXXXXXX" />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="location">Area</Label>
                        <select id="location" value={values.location_id} onChange={(e) => set('location_id', e.target.value)} className="h-9 rounded-md border bg-background px-2 text-sm">
                            <option value="">Select…</option>
                            {locations.map((l) => (<option key={l.id} value={l.id}>{l.name}</option>))}
                        </select>
                        <InputError message={errors.location_id} />
                    </div>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="address">Address</Label>
                    <Input id="address" value={values.address} onChange={(e) => set('address', e.target.value)} maxLength={255} />
                    <InputError message={errors.address} />
                </div>
                <div className="grid gap-2 md:col-span-2">
                    <Label htmlFor="description">About your shop</Label>
                    <textarea id="description" value={values.description} onChange={(e) => set('description', e.target.value)} rows={4} maxLength={1000} className="rounded-md border bg-transparent p-3 text-sm" />
                    <InputError message={errors.description} />
                </div>
                </div>
                <Button type="submit" disabled={busy} className="self-start">{profile ? 'Save profile' : 'Start selling'}</Button>
            </form>
        </>
    );
}
