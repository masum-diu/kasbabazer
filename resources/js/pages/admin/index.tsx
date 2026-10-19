import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/market';

type AdminCategory = { id: number; name: string; is_active: boolean; listings: number; children: { id: number; name: string; is_active: boolean; listings: number }[] };

type Props = {
    tab: 'reports' | 'sellers' | 'users' | 'listings' | 'payments' | 'categories';
    q: string;
    stats: { users: number; sellers: number; listings: number; pending_reports: number; pending_payments: number };
    categories?: AdminCategory[];
    payments?: { id: number; user: string; email: string; plan: string; amount: number; method: string; sender: string | null; transaction_id: string | null; status: string; ends_at: string | null; created_at: string }[];
    reports?: { id: number; type: string; target_id: number; target: string; target_url: string | null; reason: string; description: string | null; status: string; created_at: string }[];
    sellers?: { id: number; shop_name: string; owner: string; email: string; status: string; is_verified: boolean }[];
    users?: { id: number; name: string; email: string; is_active: boolean; is_admin: boolean }[];
    listings?: { id: number; slug: string; title: string; owner: string; status: string }[];
};

const tabs = ['reports', 'payments', 'sellers', 'users', 'listings', 'categories'] as const;
const opts = { preserveScroll: true, preserveState: true } as const;

export default function Admin({ tab, q, stats, payments = [], reports = [], sellers = [], users = [], listings = [], categories = [] }: Props) {
    const [search, setSearch] = useState(q);

    const go = (next: string, query?: string) =>
        router.get('/admin', { tab: next, ...(query ? { q: query } : {}) }, { preserveState: false });

    return (
        <>
            <Head title="Admin" />
            <h1 className="mb-4 text-2xl font-bold">Admin</h1>

            <dl className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {Object.entries(stats).map(([key, value]) => (
                    <div key={key} className="rounded-xl border p-4">
                        <dt className="text-xs text-muted-foreground capitalize">{key.replace('_', ' ')}</dt>
                        <dd className="text-2xl font-bold">{value}</dd>
                    </div>
                ))}
            </dl>

            <div className="mb-4 flex flex-wrap items-center gap-2 border-b pb-2">
                {tabs.map((t) => (
                    <button key={t} type="button" onClick={() => go(t)} className={cn('rounded-md px-3 py-1.5 text-sm capitalize hover:bg-accent', tab === t && 'bg-accent font-medium')}>
                        {t}
                    </button>
                ))}
                {(tab === 'users' || tab === 'listings') && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            go(tab, search);
                        }}
                        className="ml-auto flex gap-2"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search…" aria-label="Search" className="h-8 w-48" />
                        <Button type="submit" size="sm">Search</Button>
                    </form>
                )}
            </div>

            {tab === 'reports' && (
                <ul className="flex flex-col gap-3">
                    {reports.length === 0 && <li className="text-sm text-muted-foreground">No reports.</li>}
                    {reports.map((r) => (
                        <li key={r.id} className="rounded-xl border p-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant={r.status === 'pending' ? 'destructive' : 'secondary'}>{r.status}</Badge>
                                <Badge variant="outline">{r.type}</Badge>
                                <Badge variant="outline">{r.reason.replace('_', ' ')}</Badge>
                                <span className="text-xs text-muted-foreground">{timeAgo(r.created_at)}</span>
                            </div>
                            <p className="mt-2 text-sm font-medium">
                                {r.target_url ? <Link href={r.target_url} className="hover:underline">{r.target}</Link> : r.target}
                            </p>
                            {r.description && <p className="text-sm text-muted-foreground">{r.description}</p>}
                            {r.status === 'pending' && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {(r.type === 'Listing' || r.type === 'Comment') && (
                                        <Button size="sm" variant="destructive" onClick={() => router.put(`/admin/reports/${r.id}`, { status: 'resolved', remove_target: 1 }, opts)}>
                                            {r.type === 'Listing' ? 'Hide listing & resolve' : 'Delete comment & resolve'}
                                        </Button>
                                    )}
                                    <Button size="sm" variant="outline" onClick={() => router.put(`/admin/reports/${r.id}`, { status: 'resolved' }, opts)}>Resolve</Button>
                                    <Button size="sm" variant="ghost" onClick={() => router.put(`/admin/reports/${r.id}`, { status: 'dismissed' }, opts)}>Dismiss</Button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {tab === 'payments' && (
                <ul className="flex flex-col gap-2">
                    {payments.length === 0 && <li className="text-sm text-muted-foreground">No payments yet.</li>}
                    {payments.map((p) => (
                        <li key={p.id} className="flex flex-wrap items-center gap-3 rounded-xl border p-3">
                            <div className="min-w-0 flex-1">
                                <p className="font-medium">{p.user} · {p.plan} · ৳{p.amount}</p>
                                <p className="text-xs text-muted-foreground">{p.email} · {p.method} from {p.sender} · TrxID <strong>{p.transaction_id}</strong> · {timeAgo(p.created_at)}</p>
                            </div>
                            <Badge variant={p.status === 'pending' ? 'destructive' : p.status === 'active' ? 'default' : 'secondary'}>{p.status}</Badge>
                            {p.status === 'pending' && (
                                <div className="flex gap-1">
                                    <Button size="sm" onClick={() => router.post(`/admin/payments/${p.id}/confirm`, {}, opts)}>Confirm</Button>
                                    <Button size="sm" variant="outline" onClick={() => { const note = prompt('Reason (optional)') ?? undefined; router.post(`/admin/payments/${p.id}/reject`, { note }, opts); }}>Reject</Button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {tab === 'sellers' && (
                <ul className="flex flex-col gap-2">
                    {sellers.map((s) => (
                        <li key={s.id} className="flex flex-wrap items-center gap-3 rounded-xl border p-3">
                            <div className="min-w-0 flex-1">
                                <p className="font-medium">{s.shop_name}</p>
                                <p className="text-xs text-muted-foreground">{s.owner} · {s.email}</p>
                            </div>
                            <Badge variant={s.status === 'verified' ? 'default' : 'secondary'}>{s.status}</Badge>
                            <div className="flex gap-1">
                                {s.status !== 'verified' && <Button size="sm" variant="outline" onClick={() => router.put(`/admin/sellers/${s.id}`, { status: 'verified' }, opts)}>Verify</Button>}
                                {s.status !== 'suspended' && <Button size="sm" variant="outline" onClick={() => router.put(`/admin/sellers/${s.id}`, { status: 'suspended' }, opts)}>Suspend</Button>}
                                {s.status !== 'rejected' && <Button size="sm" variant="ghost" onClick={() => router.put(`/admin/sellers/${s.id}`, { status: 'rejected' }, opts)}>Reject</Button>}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {tab === 'categories' && <CategoriesPanel categories={categories} />}

            {tab === 'users' && (
                <ul className="flex flex-col gap-2">
                    {users.map((u) => (
                        <li key={u.id} className="flex flex-wrap items-center gap-3 rounded-xl border p-3">
                            <div className="min-w-0 flex-1">
                                <p className="font-medium">{u.name} {u.is_admin && <Badge className="ml-1">admin</Badge>}</p>
                                <p className="text-xs text-muted-foreground">{u.email}</p>
                            </div>
                            <Badge variant={u.is_active ? 'secondary' : 'destructive'}>{u.is_active ? 'active' : 'deactivated'}</Badge>
                            {!u.is_admin && (
                                <Button size="sm" variant="outline" onClick={() => router.post(`/admin/users/${u.id}/toggle`, {}, opts)}>
                                    {u.is_active ? 'Deactivate' : 'Activate'}
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {tab === 'listings' && (
                <ul className="flex flex-col gap-2">
                    {listings.map((l) => (
                        <li key={l.id} className="flex flex-wrap items-center gap-3 rounded-xl border p-3">
                            <div className="min-w-0 flex-1">
                                <Link href={`/listings/${l.slug}`} className="font-medium hover:underline">{l.title}</Link>
                                <p className="text-xs text-muted-foreground">by {l.owner}</p>
                            </div>
                            <Badge variant={l.status === 'active' ? 'default' : 'secondary'}>{l.status}</Badge>
                            {l.status !== 'deleted' && (
                                <div className="flex gap-1">
                                    {l.status === 'hidden' ? (
                                        <Button size="sm" variant="outline" onClick={() => router.post(`/listings/${l.slug}/status/publish`, {}, opts)}>Unhide</Button>
                                    ) : (
                                        <Button size="sm" variant="outline" onClick={() => router.post(`/listings/${l.slug}/status/hide`, {}, opts)}>Hide</Button>
                                    )}
                                    <Button size="sm" variant="ghost" onClick={() => confirm('Delete this listing?') && router.delete(`/listings/${l.slug}`, opts)}>Delete</Button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}

function CategoriesPanel({ categories }: { categories: AdminCategory[] }) {
    const [name, setName] = useState('');
    const [parent, setParent] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [editing, setEditing] = useState<{ id: number; name: string } | null>(null);

    const add = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/admin/categories', { name, parent_id: parent || null }, {
            ...opts,
            onSuccess: () => setName(''),
            onError: setErrors,
        });
    };

    const toggle = (id: number, is_active: boolean) => router.put(`/admin/categories/${id}`, { is_active: !is_active }, opts);
    const save = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            router.put(`/admin/categories/${editing.id}`, { name: editing.name }, { ...opts, onSuccess: () => setEditing(null), onError: setErrors });
        }
    };
    const remove = (id: number) => confirm('Delete this category?') && router.delete(`/admin/categories/${id}`, opts);

    const row = (c: { id: number; name: string; is_active: boolean; listings: number }, sub = false) => (
        <li key={c.id} className={cn('flex flex-wrap items-center gap-2 py-2', sub && 'pl-6')}>
            {editing?.id === c.id ? (
                <form onSubmit={save} className="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    <Input value={editing.name} onChange={(e) => setEditing({ id: c.id, name: e.target.value })} aria-label="Category name" maxLength={80} required autoFocus className="h-8 min-w-40 flex-1" />
                    <Button type="submit" size="sm">Save</Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => setEditing(null)}>Cancel</Button>
                    {errors.name && <p className="w-full text-xs text-destructive">{errors.name}</p>}
                </form>
            ) : (
                <>
                    <span className={cn('min-w-0 flex-1', !sub && 'font-semibold', !c.is_active && 'text-muted-foreground line-through')}>{c.name}</span>
                    <span className="text-xs text-muted-foreground">{c.listings} listings</span>
                    <Button size="sm" variant="outline" onClick={() => { setErrors({}); setEditing({ id: c.id, name: c.name }); }}>Edit</Button>
                    <Button size="sm" variant="outline" onClick={() => toggle(c.id, c.is_active)}>{c.is_active ? 'Hide' : 'Show'}</Button>
                    <Button size="sm" variant="ghost" onClick={() => remove(c.id)}>Delete</Button>
                </>
            )}
        </li>
    );

    return (
        <div className="flex flex-col gap-4">
            <form onSubmit={add} className="flex flex-wrap items-end gap-2 rounded-xl border bg-card p-4">
                <div className="grid min-w-48 flex-1 gap-1.5">
                    <label htmlFor="cat-name" className="text-sm font-medium">New category or sub-category</label>
                    <Input id="cat-name" value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Tablets" maxLength={80} required />
                    {(errors.name || errors.parent_id) && <p className="text-xs text-destructive">{errors.name ?? errors.parent_id}</p>}
                </div>
                <div className="grid gap-1.5">
                    <label htmlFor="cat-parent" className="text-sm font-medium">Put it under</label>
                    <select id="cat-parent" value={parent} onChange={(e) => setParent(e.target.value)} className="h-9 rounded-md border bg-background px-2 text-sm">
                        <option value="">— Top-level category —</option>
                        {categories.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
                    </select>
                </div>
                <Button type="submit">Add</Button>
            </form>

            <ul className="divide-y rounded-xl border bg-card px-4">
                {categories.map((c) => (
                    <li key={c.id}>
                        <ul>{row(c)}{c.children.map((k) => row(k, true))}</ul>
                    </li>
                ))}
            </ul>
        </div>
    );
}
