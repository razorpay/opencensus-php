<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use Slack;
use Config;
use Monolog\Logger;

use RZP\Exception;
use RZP\Trace\Tracer;
use RZP\Models\Base\Core;
use RZP\Models\Transaction;
use RZP\Constants\HyperTrace;
use RZP\Models\FundAccount\Type;
use RZP\Jobs\FundAccountValidation;
use RZP\Models\Merchant\Preferences;
use RZP\Models\FundTransfer\Attempt;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\FundAccount\Validation\Metric;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\AccountStatus;

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

    public function markValidationAsCompleted(string $accountStatus, string $utr = null)
    {
        $this->validation->setStatus(Status::COMPLETED);

        $this->validation->setAccountStatus($accountStatus);

        $this->validation->setUtr($utr);

        $this->repo->saveOrFail($this->validation);

        if (($accountStatus === AccountStatus::ACTIVE) and
            ($this->validation->getRegisteredName() === null))
        {
            $this->trace->count(Metric::FAV_COMPLETED_WITH_STATUS_ACTIVE_AND_BENE_NAME_NULL,
                                [
                                    'mode' => $this->app['rzp.mode']
                                ]);
        }

        $this->dispatchValidationCompletedEvent();

        $this->triggerValidationCompletedWebhook();
    }

    public function markValidationAsFailed()
    {
        $this->validation->setStatus(Status::FAILED);

        $this->repo->saveOrFail($this->validation);

        $this->dispatchValidationCompletedEvent();

        $this->triggerValidationFailedWebhook();
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
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->validation
        ];

        $this->app['events']->dispatch('api.fund_account.validation.completed', $eventPayload);
    }

    protected function triggerValidationFailedWebhook()
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->validation
        ];

        $this->app['events']->dispatch('api.fund_account.validation.failed', $eventPayload);
    }

    /**
     * Pushes fund account validation events to queue if
     *
     * Request is raised by merchant 100000Razorpay and fund account type is bank account
     *
     */
    protected function dispatchValidationCompletedEvent(): void
    {
        $whitelistMidsForEvent = ['100000Razorpay'];

        if ((in_array($this->validation->getMerchantId(), $whitelistMidsForEvent, true) === true)
            and ($this->validation->fundAccount->getAccountType() === Type::BANK_ACCOUNT))
        {
            FundAccountValidation::dispatch($this->mode, $this->validation->getId());
        }
    }
}
