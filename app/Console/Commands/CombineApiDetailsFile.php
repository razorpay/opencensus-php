<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Services\AutoGenerateApiDocs;
use RZP\Services\AutoGenerateApiDocs\Constants as ApiDocsConstants;

class CombineApiDetailsFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoGenerateApiDocs:CombineApiDetailsFile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combines all api details files';

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
        (new AutoGenerateApiDocs\CombineApiDetailsFile(ApiDocsConstants::TOTAL_API_DETAILS_FILES , ApiDocsConstants::FILES_DIR, AutoGenerateApiDocs\Constants::COMBINED_API_DETAILS_FILE_PATH))->combine();
    }
}
