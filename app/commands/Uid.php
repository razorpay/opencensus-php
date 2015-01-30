<?php

use Illuminate\Console\Command;
use Models\Base\UniqueIdEntity;
use Symfony\Component\Console\Input\InputOption;

class Uid extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:uid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates rzp uid';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $uid = UniqueIdEntity::generateUniqueId();

        $this->info($uid);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['timestamp', null, InputOption::VALUE_OPTIONAL, 'Convert timestamp to Uid']);

        array_push($array, ['uid', null, InputOption::VALUE_OPTIONAL, 'Convert Uid to timestamp']);

        return $array;
    }
}