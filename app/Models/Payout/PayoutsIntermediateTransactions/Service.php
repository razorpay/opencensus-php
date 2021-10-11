<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;


use RZP\Models\Base;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    /**
     * @var Core
     */
    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->payouts_intermediate_transactions;
    }

    public function updatePayoutIntermediateTransactions()
    {
        $response = $this->core->updatePayoutIntermediateTransactions();

        return $response;
    }

    public function fetchIntermediateTransactionForAGivenPayoutId(string $payoutId)
    {
        $response = $this->core->fetchIntermediateTransactionForAGivenPayoutId($payoutId);

        return $response;
    }
}

