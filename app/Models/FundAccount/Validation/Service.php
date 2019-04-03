<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Traits;

class Service extends Base\Service
{
    use Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->fund_account_validation;
    }

    public function retry(array $input): array
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_RETRY_REQUEST, [
            'input' => $input
        ]);

        $response = $this->core->retry($input);

        return $response;
    }
}
