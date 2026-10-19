<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire lapsed seller subscriptions and remove plan-based verified badges';

    public function handle(SubscriptionService $subscriptions): int
    {
        $this->info("Expired {$subscriptions->expireDue()} subscription(s).");

        return self::SUCCESS;
    }
}
