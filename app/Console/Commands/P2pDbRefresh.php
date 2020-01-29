<?php

namespace RZP\Console\Commands;

class P2pDbRefresh extends RzpDbRefresh
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p2p:dbr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes both test and live p2p databases and seeds them';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ( ! $this->confirmToProceed()) return;

        $databases = ['test', 'live'];

        foreach ($databases as $database)
        {
            $this->call('migrate',
                ['--database' => $database, '--path' => 'database/migrations/p2p']);

            $this->call('db:seed', ['--database' => $database, '--class' => 'P2pSeeder']);
        }
    }
}
