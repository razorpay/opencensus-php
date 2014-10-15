<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CaptureAll extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'custom:captureall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Captures all the pending payments, awaiting capture';

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
        //Call payment controller's capture fucntion
        $paymentController = new PaymentController;
        $response = $paymentController->capture();

        $captures=$response->getContent();

        $captures = json_decode($captures);

        //Check if output is not NULL
        if (! $captures) return;

        //Display the captured payment's ids
        foreach($captures as $capture)
        {
            if ($capture->captured)
            {
                $this->info($capture->id." Captured successfully. \n");
            }
        }
    }
}
