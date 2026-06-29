<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Jobs\ProcessSubscriptionJob;

class ProcessSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Subscription::where('status', 0)
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    ProcessSubscriptionJob::dispatch($subscription->id);
                }
            });

        return Command::SUCCESS;
    }
}
