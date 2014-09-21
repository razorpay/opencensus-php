<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Models\Settlement\MprGenerator;

class MprGenerate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:mpr_generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates mpr for test mode for today';

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
        $mode = 'test';

        $this->laravel['rzp.mode'] = $mode;
        Database\DefaultConnection::set($mode);

        $today = $this->input->getOption('today');

        if ($today)
        {
            MprGenerator::setTodayTimestamps();
        }

        $r = (new \Models\Settlement\Service)->gatewayMprGenerate();

        $this->info($r);

        $reconcile = $this->input->getOption('reconcile');

        if (($reconcile) and (is_string($r)) and (file_exists($r)))
        {
            Artisan::call('rzp:mpr_reconcile', array('--file' => $r));
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

        array_push($array, ['today', null, InputOption::VALUE_NONE, 'Will generate mpr for today\'s transactions']);

        array_push($array, ['reconcile', null, InputOption::VALUE_NONE, 'Also reconciles the mpr']);

        return $array;
    }
}
