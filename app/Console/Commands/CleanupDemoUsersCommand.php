<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Noerd\Models\User;

class CleanupDemoUsersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'demo:cleanup {--hours=24 : Delete demo users older than this many hours}';

    /**
     * @var string
     */
    protected $description = 'Delete demo users older than the specified number of hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $count = User::query()
            ->where('is_demo', true)
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();

        $this->info("Deleted {$count} demo user(s).");

        return self::SUCCESS;
    }
}
