<?php

namespace App\Console\Commands;

use App\Actions\Billing\ExpireSubscriptions;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire paid plans whose period has ended';

    public function handle(ExpireSubscriptions $action): int
    {
        $count = $action->handle();
        $this->info("Expired {$count} subscriptions.");

        return self::SUCCESS;
    }
}
