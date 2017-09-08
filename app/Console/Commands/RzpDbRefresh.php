<?php

namespace RZP\Console\Commands;

use Illuminate\Database\Console\Migrations\RefreshCommand;
use Symfony\Component\Console\Input\InputOption;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if ( ! $this->confirmToProceed()) return;

        $force = $this->input->getOption('force');

        $install = $this->input->getOption('install');

        if ($install)
        {
            $this->info('<info>Installing test database.</info>');

            $this->call('migrate', ['--database' => 'test']);

            $this->info('<info>Installing live database.</info>');

            $this->call('migrate', ['--database' => 'live']);
        }
        else
        {
            $this->info('<info>Refreshing test database.</info>');

            $this->call(
                'migrate:refresh',
                [
                    '--database' => 'test',
                    '--force' => $force
                ]);

            $this->info('<info>Refreshing live database.</info>');

            $this->call(
                'migrate:refresh',
                [
                    '--database' => 'live',
                    '--force' => $force
                ]);
        }

        if ($this->needsSeeding())
        {
            $this->info('<info>Seeding both databases.</info>');

            $this->runSeeder('test');
            $this->runSeeder('live');
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['install', null, InputOption::VALUE_NONE, 'Will only install instead of refresh']);

        return $array;
    }
}
