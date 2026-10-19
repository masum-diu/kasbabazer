<?php

namespace App\Services;

use App\Models\Boost;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\SellerProfile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function freePlan(): Plan
    {
        // Falls back to built-in limits if the plans table has not been seeded yet.
        return Plan::where('slug', 'free')->first() ?? new Plan(['slug' => 'free', 'name' => 'Free', 'price' => 0, 'listing_limit' => 5, 'photo_limit' => 3, 'boosts_per_period' => 0]);
    }

    public function activeSubscription(User $user): ?Subscription
    {
        return Subscription::with('plan')
            ->where('user_id', $user->id)->where('status', 'active')->where('ends_at', '>', now())
            ->latest('id')->first();
    }

    public function planFor(User $user): Plan
    {
        return $this->activeSubscription($user)->plan ?? $this->freePlan();
    }

    public function activeListingCount(User $user): int
    {
        return Listing::where('user_id', $user->id)->whereIn('status', ['active', 'reserved'])->count();
    }

    /**
     * Throw a validation error if publishing one more listing would exceed the plan limit.
     */
    public function assertCanPublish(User $user): void
    {
        $limit = $this->planFor($user)->listing_limit;

        if ($limit !== null && $this->activeListingCount($user) >= $limit) {
            throw ValidationException::withMessages([
                'plan' => ["Your plan allows {$limit} active listings. Upgrade your plan or mark something as sold/hidden first."],
            ]);
        }
    }

    public function photoLimit(User $user): int
    {
        return $this->planFor($user)->photo_limit;
    }

    public function boostsUsed(User $user): int
    {
        $since = $this->activeSubscription($user)->starts_at ?? now()->startOfMonth();

        return Boost::where('user_id', $user->id)->where('created_at', '>=', $since)->count();
    }

    public function boostsRemaining(User $user): int
    {
        return max(0, $this->planFor($user)->boosts_per_period - $this->boostsUsed($user));
    }

    /**
     * Put a listing at the top of the feed for a while, using one of the plan's boosts.
     */
    public function boost(Listing $listing, User $user): void
    {
        if ($listing->status !== 'active') {
            throw ValidationException::withMessages(['boost' => ['Only available listings can be boosted.']]);
        }

        if ($this->boostsRemaining($user) < 1) {
            throw ValidationException::withMessages(['boost' => ['No boosts left on your plan. Upgrade to get more.']]);
        }

        DB::transaction(function () use ($listing, $user) {
            $until = now()->addHours((int) config('billing.boost_hours'));

            $boost = new Boost;
            $boost->forceFill(['user_id' => $user->id, 'listing_id' => $listing->id, 'ends_at' => $until])->save();
            $listing->forceFill(['boosted_until' => $until])->save();
        });
    }

    /**
     * Seller submits their payment details; an admin confirms it later.
     */
    public function request(User $user, Plan $plan, string $method, string $sender, string $transactionId): Subscription
    {
        if ($plan->isFree() || ! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => ['This plan cannot be purchased.']]);
        }

        if (Subscription::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['transaction_id' => ['You already have a payment waiting for confirmation.']]);
        }

        $subscription = new Subscription(['payment_method' => $method, 'sender_number' => $sender, 'transaction_id' => $transactionId]);
        $subscription->forceFill(['user_id' => $user->id, 'plan_id' => $plan->id, 'amount' => $plan->price, 'status' => 'pending'])->save();

        foreach (User::where('is_admin', true)->get() as $admin) {
            $this->notifications->notify($admin, $user, 'seller', "{$user->name} paid ৳{$plan->price} for {$plan->name}", '/admin?tab=payments', "TrxID {$transactionId}");
        }

        return $subscription;
    }

    public function confirm(Subscription $subscription, User $admin): void
    {
        DB::transaction(function () use ($subscription, $admin) {
            // A new confirmed plan replaces whatever was active.
            Subscription::where('user_id', $subscription->user_id)->where('status', 'active')->update(['status' => 'expired']);

            $subscription->forceFill([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays((int) config('billing.period_days')),
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
            ])->save();

            $this->syncVerifiedBadge($subscription->user, $subscription->plan);
        });

        $this->notifications->notify($subscription->user, $admin, 'seller', "Your {$subscription->plan->name} plan is active", '/plans', "Valid until {$subscription->ends_at?->format('d M Y')}");
    }

    public function reject(Subscription $subscription, User $admin, ?string $note = null): void
    {
        $subscription->forceFill(['status' => 'rejected', 'confirmed_by' => $admin->id, 'confirmed_at' => now(), 'note' => $note])->save();

        $this->notifications->notify($subscription->user, $admin, 'seller', 'Your payment could not be confirmed', '/plans', $note ?? 'Please check the transaction ID and try again.');
    }

    /**
     * Mark lapsed subscriptions as expired and drop plan-based verified ticks.
     */
    public function expireDue(): int
    {
        $due = Subscription::with('user', 'plan')->where('status', 'active')->where('ends_at', '<=', now())->get();

        foreach ($due as $subscription) {
            $subscription->forceFill(['status' => 'expired'])->save();
            $this->syncVerifiedBadge($subscription->user, $this->planFor($subscription->user));
            $this->notifications->notify($subscription->user, null, 'seller', "Your {$subscription->plan->name} plan has expired", '/plans', 'Renew to keep your listing limit and verified badge.');
        }

        return $due->count();
    }

    /**
     * Plan-based tick is granted/removed here; admin-granted ticks (verified_by_plan = false) are untouched.
     */
    private function syncVerifiedBadge(User $user, Plan $plan): void
    {
        $profile = SellerProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return;
        }

        if ($plan->verified_badge) {
            $profile->forceFill(['is_verified' => true, 'verified_by_plan' => ! $profile->is_verified || $profile->verified_by_plan])->save();
        } elseif ($profile->verified_by_plan) {
            $profile->forceFill(['is_verified' => false, 'verified_by_plan' => false])->save();
        }
    }
}
