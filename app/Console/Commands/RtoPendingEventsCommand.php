<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Services\RtoOrderActionRetryHandler;

class RtoPendingEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:rtoPendingEvents
                            {mode      : Database & application mode the command will run in (test|live)}
                            {topics    : Topic where the message will be sent  }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the rto pending events command';

    /**
     * BulkUploadClient Service
     *
     * @var RtoOrderActionRetryHandler
     */
    protected $rtoOrderActionRetryHandlerService;

    /**
     * Create a new command instance.
     *
     * @param RtoOrderActionRetryHandler $rtoOrderActionRetryHandlerService;
     * @return void
     */
    public function __construct(RtoOrderActionRetryHandler $rtoOrderActionRetryHandlerService)
    {
        parent::__construct();
        $this->rtoOrderActionRetryHandlerService = $rtoOrderActionRetryHandlerService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("starting rto pending events sqs producer");

        $mode   = $this->argument('mode');

        $topics = $this->argument('topics');

        $this->info("mode: ".$mode ." topic: ".$topics);

        $this->rtoOrderActionRetryHandlerService->process($mode);

        //Preventing worker from terminating to make sure logs are published in sumo.
        sleep(60);

        $this->info("ending rto pending events sqs producer command");
    }
}

