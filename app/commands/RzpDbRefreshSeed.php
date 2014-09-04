<?php

use Illuminate\Database\Console\Migrations\RefreshCommand;

class RzpDbRefresh extends RefreshCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:dbr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes both test and live databases and seeds them';

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
    public function fire()
    {
        if ( ! $this->confirmToProceed()) return;

        $this->info('<info>Refreshing test database.</info>');

        $force = $this->input->getOption('force');

        $this->call('migrate:refresh', array(
            '--database' => 'test', '--force' => $force
        ));

        $this->info('<info>Refreshing live database.</info>');

        $this->call('migrate:refresh', array(
            '--database' => 'live', '--force' => $force
        ));

        if ($this->needsSeeding())
        {
            $this->info('<info>Seeding both databases.</info>');

            $this->runSeeder('test');
            $this->runSeeder('live');
        }
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    //     return array(
    //         array('example', InputArgument::REQUIRED, 'An example argument.'),
    //     );
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    //     return array(
    //         array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    //     );
    // }

}
