<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Listing;
use App\Models\Report;
use App\Models\SellerProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CommentService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(private readonly NotificationService $notifications, private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): Response
    {
        $tab = in_array($request->query('tab'), ['reports', 'sellers', 'users', 'listings', 'payments', 'categories'], true) ? $request->query('tab') : 'reports';
        $q = trim((string) $request->query('q', ''));

        return Inertia::render('admin/index', [
            'tab' => $tab,
            'q' => $q,
            'stats' => [
                'users' => User::count(),
                'sellers' => SellerProfile::count(),
                'listings' => Listing::count(),
                'pending_reports' => Report::where('status', 'pending')->count(),
                'pending_payments' => Subscription::where('status', 'pending')->count(),
            ],
            'categories' => fn () => $tab !== 'categories' ? [] : Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'is_active' => $c->is_active,
                    'listings' => Listing::where('category_id', $c->id)->count(),
                    'children' => $c->children->map(fn (Category $k) => ['id' => $k->id, 'name' => $k->name, 'is_active' => $k->is_active, 'listings' => Listing::where('category_id', $k->id)->count()])->values(),
                ]),
            'payments' => fn () => $tab !== 'payments' ? [] : Subscription::with(['user:id,name,email', 'plan:id,name'])->latest()->limit(50)->get()
                ->map(fn (Subscription $s) => ['id' => $s->id, 'user' => $s->user->name, 'email' => $s->user->email, 'plan' => $s->plan->name, 'amount' => $s->amount, 'method' => $s->payment_method, 'sender' => $s->sender_number, 'transaction_id' => $s->transaction_id, 'status' => $s->status, 'ends_at' => $s->ends_at?->toISOString(), 'created_at' => $s->created_at?->toISOString()]),
            'reports' => fn () => $tab !== 'reports' ? [] : Report::with('reportable')->latest()
                ->limit(50)->get()->map(fn (Report $r) => [
                    'id' => $r->id,
                    'type' => class_basename($r->reportable_type),
                    'target_id' => $r->reportable_id,
                    'target' => match (true) {
                        $r->reportable instanceof Listing => $r->reportable->title,
                        $r->reportable instanceof Comment => $r->reportable->content,
                        $r->reportable instanceof User => $r->reportable->name,
                        default => '(deleted)',
                    },
                    'target_url' => match (true) {
                        $r->reportable instanceof Listing => '/listings/'.$r->reportable->slug,
                        $r->reportable instanceof User => '/sellers/'.$r->reportable->id,
                        default => null,
                    },
                    'reason' => $r->reason,
                    'description' => $r->description,
                    'status' => $r->status,
                    'created_at' => $r->created_at?->toISOString(),
                ]),
            'sellers' => fn () => $tab !== 'sellers' ? [] : SellerProfile::with('user:id,name,email')->latest()->limit(50)->get()
                ->map(fn (SellerProfile $s) => ['id' => $s->id, 'shop_name' => $s->shop_name, 'owner' => $s->user->name, 'email' => $s->user->email, 'status' => $s->status, 'is_verified' => $s->is_verified]),
            'users' => fn () => $tab !== 'users' ? [] : User::withTrashed()
                ->when($q, fn ($query) => $query->where(fn ($w) => $w->whereLike('name', "%{$q}%")->orWhereLike('email', "%{$q}%")))
                ->latest()->limit(50)->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'is_active' => $u->is_active, 'is_admin' => $u->is_admin]),
            'listings' => fn () => $tab !== 'listings' ? [] : Listing::withTrashed()
                ->when($q, fn ($query) => $query->whereLike('title', "%{$q}%"))
                ->with('user:id,name')->latest()->limit(50)->get()
                ->map(fn (Listing $l) => ['id' => $l->id, 'slug' => $l->slug, 'title' => $l->title, 'owner' => $l->user->name, 'status' => $l->deleted_at ? 'deleted' : $l->status]),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            // Only top-level categories can be parents (two levels: category > sub-category).
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id')],
        ]);

        $slug = $base = Str::slug($data['name']) ?: 'category';
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $siblings = Category::where('parent_id', $data['parent_id'] ?? null);
        Category::create([
            'name' => trim($data['name']),
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => ((int) $siblings->max('sort_order')) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => ($data['parent_id'] ?? null) ? 'Sub-category added.' : 'Category added.']);

        return back();
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return back();
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $inUse = Listing::withTrashed()->whereIn('category_id', [$category->id, ...$category->children()->pluck('id')])->exists();

        if ($inUse || $category->children()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Has listings or sub-categories. Hide it instead of deleting.']);

            return back();
        }

        $category->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return back();
    }

    public function updateReport(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'remove_target' => ['boolean'],
        ]);

        if ($request->boolean('remove_target')) {
            $target = $report->reportable;
            if ($target instanceof Listing) {
                $target->forceFill(['status' => 'hidden'])->save();
            } elseif ($target instanceof Comment) {
                app(CommentService::class)->delete($target);
            }
        }

        $report->forceFill(['status' => $data['status'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report '.$data['status'].'.']);

        return back();
    }

    public function updateSeller(Request $request, SellerProfile $seller): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:'.implode(',', SellerProfile::STATUSES)]])['status'];

        $seller->forceFill(['status' => $status, 'is_verified' => $status === 'verified'])->save();
        $this->notifications->notify($seller->user, $request->user(), 'seller', "Your shop is now {$status}", '/seller/apply');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Seller marked {$status}."]);

        return back();
    }

    public function confirmPayment(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Payment already handled.');
        $this->subscriptions->confirm($subscription, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment confirmed, plan activated.']);

        return back();
    }

    public function rejectPayment(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422, 'Payment already handled.');
        $this->subscriptions->reject($subscription, $request->user(), $request->validate(['note' => ['nullable', 'string', 'max:200']])['note'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment rejected.']);

        return back();
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot deactivate yourself.');

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        // Revoke API tokens and sessions' remember tokens when deactivating.
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $user->is_active ? 'User activated.' : 'User deactivated.']);

        return back();
    }
}
