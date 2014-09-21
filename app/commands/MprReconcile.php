<?php

use Illuminate\Console\Command;
use Models\Settlement\MprGenerator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MprReconcile extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:mpr_reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconciles the given mpr file';

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

        $mprFile = $this->input->getOption('file');

        $mimeType = 'application/vnd.ms-excel';
        $mprUploadedFile = new UploadedFile($mprFile, $mprFile, $mimeType, filesize($mprFile), null, true);

        $input['mpr'] = $mprUploadedFile;
        $input['gateway'] = 'hdfc';

        $r = (new \Models\Settlement\Service)->gatewayMprReconcile($input);
        $r = json_encode($r);
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

        array_push($array, ['file', 'f', InputOption::VALUE_REQUIRED, 'Reconcile the txns in the mpr file being provided']);

        return $array;
    }
}
