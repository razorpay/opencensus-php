<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;

class PaymentsUpiDbRefresh extends RzpDbRefresh
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'payments_upi:dbr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command takes care of Payments UPI database migration';

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

        $paymentsUpiConnections = [
            'payments_upi_test',
            'payments_upi_live',
        ];

        foreach ($paymentsUpiConnections as $name)
        {
            $this->info('Running migration for "' . $name . '"');

            $this->call($command, [
                '--database'    => $name,
                '--path'        => 'database/migrations/payments_upi',
                '--force'       => $force
            ]);

        }
    }
}
