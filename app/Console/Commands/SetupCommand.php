<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    protected $description = 'Setup the application with migrations, seeds and keys.';

    public function handle(): int
    {
        $this->info('Starting application setup...');

        $this->call('key:generate');
        $this->info('Application key generated.');

        $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
        $this->info('Database migrated and seeded successfully.');

        $this->info('Application setup completed!');

        return self::SUCCESS;
    }
}
