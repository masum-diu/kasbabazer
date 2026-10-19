<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Pricing page; signed-in sellers also see their plan, usage and payment history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $current = $user ? $this->subscriptions->planFor($user) : null;
        $active = $user ? $this->subscriptions->activeSubscription($user) : null;

        return Inertia::render('plans/index', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get()->map(fn (Plan $p) => $this->plan($p)),
            'current' => $current ? ['slug' => $current->slug, 'name' => $current->name, 'ends_at' => $active?->ends_at?->toISOString()] : null,
            'usage' => $user ? [
                'listings' => $this->subscriptions->activeListingCount($user),
                'listing_limit' => $current->listing_limit,
                'boosts_left' => $this->subscriptions->boostsRemaining($user),
                'boosts_total' => $current->boosts_per_period,
            ] : null,
            'pending' => $user ? Subscription::with('plan')->where('user_id', $user->id)->where('status', 'pending')->latest()->first()?->only(['id', 'amount', 'transaction_id']) : null,
            'history' => $user ? Subscription::with('plan')->where('user_id', $user->id)->latest()->limit(10)->get()->map(fn (Subscription $s) => [
                'id' => $s->id,
                'plan' => $s->plan->name,
                'amount' => $s->amount,
                'status' => $s->status,
                'transaction_id' => $s->transaction_id,
                'ends_at' => $s->ends_at?->toISOString(),
                'created_at' => $s->created_at?->toISOString(),
            ]) : [],
        ]);
    }

    public function checkout(Plan $plan): Response
    {
        abort_if($plan->isFree() || ! $plan->is_active, 404);

        return Inertia::render('plans/checkout', [
            'plan' => $this->plan($plan),
            'pay' => [
                'bkash' => config('billing.bkash_number'),
                'nagad' => config('billing.nagad_number'),
                'days' => config('billing.period_days'),
            ],
        ]);
    }

    public function store(Request $request, Plan $plan): RedirectResponse
    {
        // Normalise first so uniqueness is checked case-insensitively.
        $request->merge(['transaction_id' => strtoupper(trim((string) $request->input('transaction_id')))]);

        $data = $request->validate([
            'payment_method' => ['required', 'in:bkash,nagad'],
            'sender_number' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'transaction_id' => ['required', 'string', 'alpha_num', 'min:6', 'max:40', 'unique:subscriptions,transaction_id'],
        ]);

        $this->subscriptions->request($request->user(), $plan, $data['payment_method'], $data['sender_number'], $data['transaction_id']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment submitted. We will confirm it shortly.']);

        return to_route('plans');
    }

    /**
     * @return array<string, mixed>
     */
    private function plan(Plan $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'price' => $p->price,
            'listing_limit' => $p->listing_limit,
            'photo_limit' => $p->photo_limit,
            'boosts_per_period' => $p->boosts_per_period,
            'verified_badge' => $p->verified_badge,
        ];
    }
}
