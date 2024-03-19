<?php

namespace RZP\Console\Commands;

class AccountServiceDbRefresh extends RzpDbRefresh
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'asv:dbr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command takes care of account service database migration';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) return;

        $command = 'migrate';
        $force = $this->option('force');

        $connections = [
            'account_service_writer',
        ];

        foreach ($connections as $name)
        {
            $this->info('Running migration for "' . $name . '"');

            $this->call($command, [
                '--database'    => $name,
                '--path'        => 'database/migrations/asv',
                '--force'       => $force
            ]);

        }
    }
}
