<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use Slack;
use Config;
use Monolog\Logger;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Models\Transaction;
use RZP\Models\FundTransfer\Attempt;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Constants;

abstract class Base extends Core
{
    protected $validation;

    protected $account;

    protected $slack;

    protected $txnCore;

    public function __construct(Entity $validation)
    {
        parent::__construct();

        $this->validation = $validation;

        $this->account = $validation->fundAccount->account;

        $this->slack = Slack::getFacadeRoot();

        $this->txnCore = new Transaction\Core();
    }

    public abstract function validateRetry();

    public abstract function preProcessValidation();

    public abstract function setDefaultValuesForValidation();

    /**
     * Updates validation entity when FTA is initiated
     * @param Attempt\Entity $fta
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaInitiated(Attempt\Entity $fta)
    {
        throw new Exception\LogicException('Not supported for source type: ' . json_encode($this->validation->getFundAccountType()));
    }

    /**
     * Updates validation entity before FTA recon
     *
     * @param array $input
     * @throws Exception\LogicException
     */
    public function updateWithDetailsBeforeFtaRecon(array $input)
    {
        throw new Exception\LogicException('Not supported for source type: ' . json_encode($this->validation->getFundAccountType()));
    }

    /**
     * Updates validation entity after FTA recon
     *
     * @param array $input
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaRecon(array $input)
    {
        throw new Exception\LogicException('Not supported for source type: ' . json_encode($this->validation->getFundAccountType()));
    }

    public function markValidationAsCompleted(string $accountStatus)
    {
        $this->validation->setStatus(Status::COMPLETED);

        $this->validation->setAccountStatus($accountStatus);

        $this->repo->saveOrFail($this->validation);

        $this->triggerValidationCompletedWebhook();
    }

    /**
     * @return Transaction\Entity
     * @throws Exception\LogicException
     */
    public function createTransaction(): Transaction\Entity
    {
        list ($txn, $feeSplit) = $this->txnCore->createTransactionForSource($this->validation);

        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

        $this->repo->saveOrFail($txn);

        return $txn;
    }

    protected function triggerValidationCompletedWebhook()
    {
        // TODO: Webhook should never be fired inside a Transaction.

        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->validation
        ];

        $this->app['events']->fire('api.fund_account.validation.completed', $eventPayload);
    }
}
