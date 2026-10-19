import { router, usePage } from '@inertiajs/react';
import { BadgeCheck, ChevronDown, ChevronUp, ThumbsUp } from 'lucide-react';
import { useState } from 'react';
import UserAvatar from '@/components/market/user-avatar';
import ReportButton from '@/components/market/report-button';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/market';
import type { Comment } from '@/types/market';

const MAX_INDENT_DEPTH = 5;
// Comments are lazy-loaded, so mutations refresh only what changed instead of the whole page.
const REFRESH = ['comments', 'listing'];

function CommentForm({
    listingSlug,
    parentId,
    placeholder,
    autoFocus,
    onDone,
}: {
    listingSlug: string;
    parentId?: number;
    placeholder: string;
    autoFocus?: boolean;
    onDone?: () => void;
}) {
    const { auth } = usePage().props;
    const [content, setContent] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!auth.user) {
            router.visit('/sign-in');

            return;
        }

        router.post(
            `/listings/${listingSlug}/comments`,
            { content, parent_id: parentId ?? null },
            {
                preserveScroll: true,
                only: REFRESH,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
                onError: (errors) => setError(Object.values(errors)[0] ?? 'Could not post comment.'),
                onSuccess: () => {
                    setContent('');
                    setError(null);
                    onDone?.();
                },
            },
        );
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-1">
            <div className="flex gap-2">
                <textarea
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    placeholder={placeholder}
                    autoFocus={autoFocus}
                    rows={1}
                    maxLength={1000}
                    aria-label={placeholder}
                    className="min-h-9 flex-1 resize-y rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <Button type="submit" size="sm" disabled={busy || content.trim() === ''}>
                    {parentId ? 'Reply' : 'Post'}
                </Button>
            </div>
            {error && <p className="text-xs text-destructive">{error}</p>}
        </form>
    );
}

function CommentNode({ comment, listingSlug, depth }: { comment: Comment; listingSlug: string; depth: number }) {
    const [replying, setReplying] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(comment.content);
    const hasReplies = comment.replies.length > 0;

    const like = () => router.post(`/comments/${comment.id}/reaction`, {}, { preserveScroll: true, only: REFRESH });
    const saveEdit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(`/comments/${comment.id}`, { content: draft }, { preserveScroll: true, only: REFRESH, onSuccess: () => setEditing(false) });
    };
    const remove = () => {
        if (confirm('Delete this comment and its replies?')) {
            router.delete(`/comments/${comment.id}`, { preserveScroll: true, only: REFRESH });
        }
    };

    return (
        <li className="flex gap-2">
            <UserAvatar name={comment.user.name} src={comment.user.avatar} className="mt-0.5 size-8" />
            <div className="min-w-0 flex-1">
                <div className="inline-block max-w-full rounded-2xl bg-muted px-3 py-2">
                    <p className="flex flex-wrap items-center gap-x-1 text-sm font-medium">
                        {comment.user.name}
                        {comment.seller_badge && (
                            <span className="inline-flex items-center gap-0.5 text-xs font-normal text-primary">
                                · <BadgeCheck className="size-3" /> {comment.seller_badge}
                            </span>
                        )}
                    </p>
                    {editing ? (
                        <form onSubmit={saveEdit} className="mt-1 flex flex-col gap-1">
                            <textarea value={draft} onChange={(e) => setDraft(e.target.value)} rows={2} maxLength={1000} aria-label="Edit comment" className="w-full rounded-md border bg-background p-2 text-sm" />
                            <div className="flex gap-2">
                                <Button type="submit" size="sm" disabled={draft.trim() === ''}>Save</Button>
                                <Button type="button" size="sm" variant="ghost" onClick={() => { setEditing(false); setDraft(comment.content); }}>Cancel</Button>
                            </div>
                        </form>
                    ) : (
                        <p className="text-sm break-words whitespace-pre-wrap">{comment.content}</p>
                    )}
                </div>

                <div className="mt-0.5 flex flex-wrap items-center gap-3 px-2 text-xs text-muted-foreground">
                    <button type="button" onClick={like} className={cn('flex items-center gap-1 font-medium hover:underline', comment.viewer_liked && 'text-primary')}>
                        <ThumbsUp className="size-3" /> {comment.likes_count > 0 ? comment.likes_count : 'Like'}
                    </button>
                    <button type="button" onClick={() => setReplying((v) => !v)} className="font-medium hover:underline">
                        Reply
                    </button>
                    <span>{timeAgo(comment.created_at)}</span>
                    {comment.can_edit && (
                        <button type="button" onClick={() => setEditing(true)} className="hover:underline">
                            Edit
                        </button>
                    )}
                    {comment.can_delete && (
                        <button type="button" onClick={remove} className="hover:text-destructive hover:underline">
                            Delete
                        </button>
                    )}
                    {!comment.can_edit && <ReportButton type="comment" id={comment.id} compact />}
                    {hasReplies && (
                        <button type="button" onClick={() => setCollapsed((v) => !v)} className="flex items-center gap-0.5 hover:underline">
                            {collapsed ? <ChevronDown className="size-3" /> : <ChevronUp className="size-3" />}
                            {collapsed ? `Show ${comment.replies.length} repl${comment.replies.length > 1 ? 'ies' : 'y'}` : 'Hide replies'}
                        </button>
                    )}
                </div>

                {replying && (
                    <div className="mt-2">
                        <CommentForm
                            listingSlug={listingSlug}
                            parentId={comment.id}
                            placeholder={`Reply to ${comment.user.name}…`}
                            autoFocus
                            onDone={() => setReplying(false)}
                        />
                    </div>
                )}

                {hasReplies && !collapsed && (
                    // Stop indenting past a few levels so deep threads stay readable on phones.
                    <ul className={cn('mt-2 flex flex-col gap-3', depth < MAX_INDENT_DEPTH && 'border-l pl-3')}>
                        {comment.replies.map((reply) => (
                            <CommentNode key={reply.id} comment={reply} listingSlug={listingSlug} depth={depth + 1} />
                        ))}
                    </ul>
                )}
            </div>
        </li>
    );
}

export default function CommentThread({ comments, listingSlug, total }: { comments: Comment[]; listingSlug: string; total: number }) {
    return (
        <section aria-labelledby="comments-heading" className="flex flex-col gap-4">
            <h2 id="comments-heading" className="text-lg font-semibold">
                Comments ({total})
            </h2>
            <CommentForm listingSlug={listingSlug} placeholder="Ask the seller a question…" />
            {comments.length === 0 ? (
                <p className="text-sm text-muted-foreground">No comments yet. Be the first to ask something.</p>
            ) : (
                <ul className="flex flex-col gap-4">
                    {comments.map((comment) => (
                        <CommentNode key={comment.id} comment={comment} listingSlug={listingSlug} depth={1} />
                    ))}
                </ul>
            )}
        </section>
    );
}
