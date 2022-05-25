<?php


namespace RZP\Models\CreditTransfer;

use RZP\Models\Base;
use RZP\Models\Payout\Batch\Core;
use RZP\Models\Payout\Batch\Repository;

class Service extends Base\Service
{
    /**
     * @var Repository
     */
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->entityRepo = $this->repo->credit_transfer;

        $this->core = (new Core());
    }
}
