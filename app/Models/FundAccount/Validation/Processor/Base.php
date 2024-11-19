<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use App;
use Slack;
use Config;
use Monolog\Logger;

use RZP\Constants;
use RZP\Exception;
use RZP\Trace\Tracer;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Models\Transaction;
use RZP\Constants\HyperTrace;
use RZP\Models\FundAccount\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\FundAccountValidation;
use RZP\Models\Merchant\Preferences;
use RZP\Models\FundTransfer\Attempt;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\PayoutUsageEventProcessing;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\FundAccount\Validation\Metric;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Constants as FavConstants;

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

    public function markValidationAsCompleted(string $accountStatus, string $utr = null, string $errDesc = null, string $errorCode = null)
    {
        $previousStatus = $this->validation->getStatus();

        $this->validation->setStatus(Status::COMPLETED);

        $this->validation->setErrorCode($errorCode);

        $this->validation->setAccountStatus($accountStatus);

        $this->validation->setErrorDescription($errDesc);

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

        (new Metric)->pushFAVCompletedMetrics($this->validation);

        $this->pushFAVStatusChangeEvent($this->validation);
    }

    public function markValidationAsFailed(string $errorCode = null, string $errorDescription = null)
    {
        $this->validation->setStatus(Status::FAILED);

        $this->validation->setErrorCode($errorCode);

        $this->validation->setErrorDescription($errorDescription);

        $this->repo->saveOrFail($this->validation);

        $this->dispatchValidationCompletedEvent();

        $this->triggerValidationFailedWebhook();

        $this->pushFAVStatusChangeEvent($this->validation);
    }

    public function pushFAVStatusChangeEvent(Entity $fav)
    {
        $eventCode = EventCode::FUND_ACCOUNT_VALIDATION_STATUS_EVENT;

        $this->app['diag']->trackFundAccountValidationStatusEvent(
            $eventCode,
            $fav);
    }

    public function pushChargeCollectionEvent(Entity $fav, string $previousStatus = null, string $errDesc = null)
    {
        $app = App::getFacadeRoot();

        try {
            // razorx call
            $variant = $app['razorx']->getTreatment(
                $fav->getMerchantId(),
                RazorxTreatment::SEND_CHARGE_COLLECTION_EVENT_RX,
                Constants\Mode::LIVE);

            if ($variant !== RazorxTreatment::RAZORX_VARIANT_ON) {
                return;
            }

            if ($previousStatus === $fav->getStatus())
            {
                return;
            }

            $app['trace']->info(
                TraceCode::CHARGE_COLLECTION_EVENT_PS_DISPATCH,
                [
                    'entity_id'   => $fav->getPublicId(),
                    'entity_type' => Constants\Entity::FUND_ACCOUNT_VALIDATION,
                ]
            );

            $isBankAccountValidation = ($this->validation->fundAccount->getAccountType() === Type::BANK_ACCOUNT);

            $eventPayload = [
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_ID                    => $fav->getPublicId(),
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_MERCHANT_ID           => $fav->getMerchantId(),
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_MODE                  => $isBankAccountValidation ? FavConstants::MODE_IMPS : '',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_STATUS                => strtolower($fav->getStatus()),
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_AMOUNT                => $isBankAccountValidation ? 1.0 : 0.0,
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_INTERFACE             => 'api',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_AGGREGATION           => 'single',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_EVENT_TYPE            => $isBankAccountValidation ? 'bank_account_validation' : 'vpa_validation',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_PAYLOAD_SOURCE        => 'vanilla',
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_FEATURE               => $this->getFeatureForChargeCollectionEvent($fav, $errDesc),
                Constants\ChargeCollections::CHARGE_COLLECTION_EVENT_PUSH_PS_SOURCE_ACCOUNT_NUMBER => '', // TO pass account number once we deduct FAV amount from CA.
            ];

            $queueName = $app['config']->get('queue.payout_usage_event_processing.' . Constants\Mode::LIVE);

            $app['queue']->connection('sqs')->pushRaw(json_encode([
                "entity_id" => $fav->getPublicId(),
                "entity_type" => Constants\Entity::FUND_ACCOUNT_VALIDATION,
                "payload" => $eventPayload,
            ]), $queueName);
        }
        catch (\Throwable $exception)
        {
            $app['trace']->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::CHARGE_COLLECTION_PS_EVENT_EXCEPTION,
                [
                    'fav_id' => $fav->getPublicId(),
                ]
            );
        }
    }

    public function getFeatureForChargeCollectionEvent(Entity $fav, string $errDesc = null) : string
    {
        switch (strtolower($errDesc))
        {
            case strtolower(FavConstants::PENNILESS):
                return strtolower(FavConstants::PENNILESS);

            case strtolower(FavConstants::CACHE):
                return strtolower(FavConstants::VALIDATION);
        }

        return strtolower(FavConstants::PENNYDROP);
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
