<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Models\Settlement\MprGenerator;
use Carbon\Carbon;

class SettlementGenerate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:settlement_generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates settlements';

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

        $yesterday = $this->input->getOption('yesterday');
        $tomorrow = $this->input->getOption('tomorrow');

        $ts = null;
        if ($yesterday)
        {
            $ts = Carbon::yesterday('Asia/Kolkata')->timestamp;
        }
        else if ($tomorrow)
        {
            $ts = Carbon::tomorrow('Asia/Kolkata')->timestamp;
        }
        else
        {
            $ts = Carbon::today('Asia/Kolkata')->timestamp;
        }

        \Models\Settlement\Settler::$settlementTimestamp = $ts;

        $r = (new \Models\Settlement\Service)->generateSettlements($input);

        $this->info($r);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['yesterday', null, InputOption::VALUE_NONE, 'Will generate mpr for for yesterday']);

        array_push($array, ['timestmap', null, InputOption::VALUE_OPTIONAL, 'Timestamp for which the settlements will take place']);

        array_push($array, ['tomorrow', null, InputOption::VALUE_OPTIONAL, 'Will generate settlements for tomorrow']);

        return $array;
    }
}
