<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Models\Base\UniqueIdEntity;
use Symfony\Component\Console\Input\InputOption;

class UidCheckDigitVerify extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:uid_check_digit_verify';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies check digit on Uid';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $uid = $this->input->getOption('uid');

        $ret = UniqueIdEntity::isValidBase62Id($uid);

        if ($ret === true)
        {
            $this->info('<info>Correct check digit.</info>');
        }
        else
        {
            $this->info('<info>Wrong check digit.</info>');

            $digit = UniqueIdEntity::getCheckDigit($uid);

            $this->info('Correct check digit is: ' . $digit);
        }

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

        array_push($array, ['uid', null, InputOption::VALUE_REQUIRED, 'Uid to check']);

        return $array;
    }
}
