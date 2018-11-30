<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Models\Base;

/**
 * Class Service
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

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

        $this->core = new Core;

        $this->entityRepo = $this->repo->beneficiary_account;
    }
}
