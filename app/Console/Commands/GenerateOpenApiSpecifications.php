<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Services\AutoGenerateApiDocs;
use RZP\Services\AutoGenerateApiDocs\Constants as ApiDocsConstants;

class GenerateOpenApiSpecifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoGenerateApiDocs:GenerateOpenApiSpecifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate open api specification from combined api details files';

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
        if(file_exists(ApiDocsConstants::COMBINED_API_DETAILS_FILE_PATH) === true)
        {
            (new AutoGenerateApiDocs\GenerateOpenApiSpecifications(ApiDocsConstants::COMBINED_API_DETAILS_FILE_PATH, ApiDocsConstants::FILES_DIR, ApiDocsConstants::OPEN_API_SPEC_FILE_NAME))->generate();
        }
    }
}
