<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Services\BulkUploadClient;
use RZP\Services\RawAddressContacts;

class RawAddressContactsProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:rawAddressContacts 
                            {mode      : Database & application mode the command will run in (test|live)}
                            {topics    : KafkaTopic to be consumed   }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the raw_address.contacts cron.';

    /**
     * BulkUploadClient Service
     *
     * @var BulkUploadClient
     */
    protected $bulkUploadClientService;

    /**
     * Create a new command instance.
     *
     * @param BulkUploadClient $bulkUploadClientService;
     * @return void
     */
    public function __construct(BulkUploadClient $bulkUploadClientService)
    {
        parent::__construct();
        $this->bulkUploadClientService = $bulkUploadClientService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("starting raw_address.contacts kafka producer");

        $mode   = $this->argument('mode');

        $topics = $this->argument('topics');

        $this->info("mode: ".$mode ." topic: ".$topics);

        $this->bulkUploadClientService->process($mode);

        sleep(60);
    }
}
