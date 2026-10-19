import { Head, Link, router } from '@inertiajs/react';
import { ImagePlus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { conditionLabels } from '@/lib/market';
import type { Category, Listing } from '@/types/market';

type Props = {
    listing: Listing | null;
    categories: Category[];
    plan: { name: string; listing_limit: number | null; photo_limit: number; active_listings: number; boosts_left: number };
};


export default function ListingForm({ listing, categories, plan }: Props) {
    const MAX_IMAGES = plan.photo_limit;
    const atLimit = plan.listing_limit !== null && plan.active_listings >= plan.listing_limit;
    const editing = listing !== null;
    const [values, setValues] = useState({
        title: listing?.title ?? '',
        description: listing?.description ?? '',
        category_id: listing?.category ? String(listing.category.id) : '',
        price: listing ? String(listing.price) : '',
        is_negotiable: listing?.is_negotiable ?? false,
        condition: listing?.condition ?? 'used_good',
        quantity: String(listing?.quantity ?? 1),
    });
    const [files, setFiles] = useState<File[]>([]);
    const [removed, setRemoved] = useState<number[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const existing = (listing?.images ?? []).filter((i) => !removed.includes(i.id));
    const previews = useMemo(() => files.map((f) => URL.createObjectURL(f)), [files]);
    const slots = MAX_IMAGES - existing.length - files.length;

    const set = <K extends keyof typeof values>(key: K, value: (typeof values)[K]) =>
        setValues((v) => ({ ...v, [key]: value }));

    const submit = (asDraft: boolean) => (e: React.SyntheticEvent) => {
        e.preventDefault();

        router.post(
            editing ? `/listings/${listing.slug}/update` : '/listings',
            {
                ...values,
                is_negotiable: values.is_negotiable ? 1 : 0,
                save_as_draft: asDraft ? 1 : 0,
                images: files,
                remove_image_ids: removed,
            },
            {
                forceFormData: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
                onError: (errs) => setErrors(errs),
            },
        );
    };

    const categoryOptions = categories.flatMap((c) => [
        <option key={c.id} value={c.id}>{c.name}</option>,
        ...(c.children ?? []).map((child) => (
            <option key={child.id} value={child.id}>{'  '}↳ {child.name}</option>
        )),
    ]);

    return (
        <>
            <Head title={editing ? 'Edit listing' : 'Sell a product'} />
            <form onSubmit={submit(false)} className="flex flex-col gap-5">
                <h1 className="text-2xl font-bold">{editing ? 'Edit listing' : 'Sell a product'}</h1>

                <div className={`flex flex-wrap items-center justify-between gap-2 rounded-xl border p-3 text-sm ${atLimit ? 'border-amber-500/50 bg-amber-500/10' : 'bg-card'}`}>
                    <p>
                        <strong>{plan.name} plan</strong>: {plan.active_listings} / {plan.listing_limit ?? '∞'} active listings · up to {plan.photo_limit} photos
                        {atLimit && ' · limit reached, save as draft or upgrade'}
                    </p>
                    <Link href="/plans" className="font-medium text-primary hover:underline">See plans</Link>
                </div>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                <div className="grid gap-2 rounded-xl border bg-card p-4">
                    <Label>Photos (up to {MAX_IMAGES}, JPG/PNG/WebP, max 4 MB each)</Label>
                    <div className="flex flex-wrap gap-2">
                        {existing.map((image) => (
                            <div key={image.id} className="relative size-24 overflow-hidden rounded-lg border">
                                <img src={image.url} alt="" className="size-full object-cover" />
                                <button type="button" aria-label="Remove photo" onClick={() => setRemoved((r) => [...r, image.id])} className="absolute top-1 right-1 rounded-full bg-background/90 p-0.5">
                                    <X className="size-3" />
                                </button>
                            </div>
                        ))}
                        {previews.map((src, i) => (
                            <div key={src} className="relative size-24 overflow-hidden rounded-lg border">
                                <img src={src} alt="" className="size-full object-cover" />
                                <button type="button" aria-label="Remove photo" onClick={() => setFiles((f) => f.filter((_, idx) => idx !== i))} className="absolute top-1 right-1 rounded-full bg-background/90 p-0.5">
                                    <X className="size-3" />
                                </button>
                            </div>
                        ))}
                        {slots > 0 && (
                            <label className="flex size-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed text-xs text-muted-foreground hover:bg-accent">
                                <ImagePlus className="size-5" />
                                Add
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    className="sr-only"
                                    onChange={(e) => {
                                        const picked = Array.from(e.target.files ?? []).slice(0, slots);
                                        setFiles((f) => [...f, ...picked]);
                                        e.target.value = '';
                                    }}
                                />
                            </label>
                        )}
                    </div>
                    <InputError message={errors.images ?? errors['images.0'] ?? errors.plan} />
                </div>

                <div className="flex flex-col gap-5 rounded-xl border bg-card p-4">
                <div className="grid gap-2">
                    <Label htmlFor="title">Title</Label>
                    <Input id="title" value={values.title} onChange={(e) => set('title', e.target.value)} maxLength={120} required />
                    <InputError message={errors.title} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <select id="category" value={values.category_id} onChange={(e) => set('category_id', e.target.value)} required className="h-9 rounded-md border bg-background px-2 text-sm">
                            <option value="">Select…</option>
                            {categoryOptions}
                        </select>
                        <InputError message={errors.category_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="condition">Condition</Label>
                        <select id="condition" value={values.condition} onChange={(e) => set('condition', e.target.value)} className="h-9 rounded-md border bg-background px-2 text-sm">
                            {Object.entries(conditionLabels).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                        <InputError message={errors.condition} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="price">Price (৳)</Label>
                        <Input id="price" type="number" min={0} value={values.price} onChange={(e) => set('price', e.target.value)} required />
                        <InputError message={errors.price} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="quantity">Quantity</Label>
                        <Input id="quantity" type="number" min={1} value={values.quantity} onChange={(e) => set('quantity', e.target.value)} required />
                        <InputError message={errors.quantity} />
                    </div>
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={values.is_negotiable} onChange={(e) => set('is_negotiable', e.target.checked)} />
                    Price is negotiable
                </label>

                <div className="grid gap-2">
                    <Label htmlFor="description">Description</Label>
                    <textarea id="description" value={values.description} onChange={(e) => set('description', e.target.value)} rows={6} maxLength={5000} required className="rounded-md border bg-transparent p-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50" />
                    <InputError message={errors.description} />
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button type="submit" disabled={busy}>{editing ? 'Save changes' : 'Publish'}</Button>
                    {!editing && <Button type="button" variant="outline" disabled={busy} onClick={submit(true)}>Save as draft</Button>}
                    <Button type="button" variant="ghost" onClick={() => history.back()}>Cancel</Button>
                </div>
                </div>
                </div>
            </form>
        </>
    );
}
