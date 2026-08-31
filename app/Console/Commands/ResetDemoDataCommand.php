<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDemoDataCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * @var string
     */
    protected $description = 'Reset the demo database to its seeded state';

    public function handle(): int
    {
        $this->info('Resetting demo database...');

        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        // The seeded media files are served through the public/storage symlink,
        // which does not exist on a freshly deployed environment.
        Artisan::call('storage:link');

        $this->info('Demo database has been reset successfully.');

        return self::SUCCESS;
    }
}
