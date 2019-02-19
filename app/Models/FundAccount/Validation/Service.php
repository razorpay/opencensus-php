<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Base;
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
}
