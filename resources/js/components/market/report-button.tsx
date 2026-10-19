import { router, usePage } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const reasons: [string, string][] = [
    ['scam', 'Scam or fraud'],
    ['fake_product', 'Fake product'],
    ['spam', 'Spam'],
    ['harassment', 'Harassment'],
    ['illegal', 'Illegal item'],
    ['wrong_information', 'Wrong information'],
    ['other', 'Other'],
];

export default function ReportButton({ type, id, compact }: { type: 'listing' | 'comment' | 'user'; id: number; compact?: boolean }) {
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState('scam');
    const [description, setDescription] = useState('');
    const [busy, setBusy] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/reports', { type, id, reason, description }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => {
                setOpen(false);
                setDescription('');
            },
        });
    };

    const trigger = () => (auth.user ? setOpen(true) : router.visit('/sign-in'));

    return (
        <>
            {compact ? (
                <button type="button" onClick={trigger} className="hover:text-destructive hover:underline">
                    Report
                </button>
            ) : (
                <Button type="button" variant="ghost" size="sm" onClick={trigger} className="gap-1 text-muted-foreground">
                    <Flag className="size-4" /> Report
                </Button>
            )}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Report {type}</DialogTitle>
                        <DialogDescription>Tell us what is wrong. Our team will review it.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="flex flex-col gap-3">
                        <label className="flex flex-col gap-1 text-sm">
                            Reason
                            <select value={reason} onChange={(e) => setReason(e.target.value)} className="h-9 rounded-md border bg-background px-2">
                                {reasons.map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-1 text-sm">
                            Details (optional)
                            <textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={3} maxLength={1000} className="rounded-md border bg-transparent p-2" />
                        </label>
                        <Button type="submit" disabled={busy}>Submit report</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
