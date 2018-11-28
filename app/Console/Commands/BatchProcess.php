<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Services\Batch as BatchService;

class BatchProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:process 
                            {id        : batch_id which needs to process} 
                            {mode      : Database & application mode the command will run in (test|live)}' ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the batch entries in background.';

    /**
     * Batch Service
     *
     * @var BatchService
     */
    protected $batchService;

    /**
     * Create a new command instance.
     *
     * @param BatchService $batchService
     * @return void
     */
    public function __construct(BatchService $batchService)
    {
        parent::__construct();
        $this->batchService = $batchService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $batchId    = $this->argument('id');
        $mode       = $this->argument('mode');

        $this->batchService->process($batchId, $mode);
    }
}
