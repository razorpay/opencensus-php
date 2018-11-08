<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RZP\Models\Card\IIN\Import\XLSImporter;

class IinImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'rzp:IinImport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Iin Database';

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
        $filename = $this->argument('filename');
        $errMsg = (new XLSImporter)->importWithoutNetwork($filename);

        print "Iin Database Update failed for following Keys\n";
        print_r($errMsg);
    }

    protected function getArguments()
    {
        return [
            ['filename', InputArgument::REQUIRED, 'Filename for reading IIn data'],
        ];
    }
}
