<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Models\Customer\Token\Core;
use RZP\Models\Merchant\Repository;
use Symfony\Component\Console\Input\InputArgument;

class ExportCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:export-customers {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports all the customers in a csv file';

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
        $filePath   = $this->argument("file");

        $file = fopen($filePath,"r");
        while (feof($file) === false)
        {
            $jsonString = fgets($file);

            if ($jsonString === false) break;

            $cardDetails = json_decode($jsonString, true);
            echo '"' . $cardDetails['name_on_card'] . '",' . $cardDetails['customer_id'];
        }
        fclose($file);
    }
}
