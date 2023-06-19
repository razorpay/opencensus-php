<?php

namespace RZP\Models\Payout\Processor;

use App;
use Closure;
use Razorpay\Api\VirtualAccount;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Http\Route;
use RZP\Error\Error;
use RZP\Constants\Mode;
use RZP\Models\Workflow;
use RZP\Constants\Timezone;
use RZP\Models\SubVirtualAccount;
use RZP\Exception\LogicException;
use RZP\Models\Feature\Constants;
use RZP\Constants as RzpConstants;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Services\Mutex;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Settings;
use RZP\Constants\Product;
use Razorpay\Trace\Logger;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\FundTransfer;
use RZP\Models\PayoutSource;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Metric;
use RZP\Models\WalletAccount;
use RZP\Models\Payout\Entity;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Credits;
use RZP\Models\Internal\Service;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payout\Notifications;
use RZP\Models\Payout\CounterHelper;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Payout\QueuedReasons;
use RZP\Jobs\PayoutPostCreateProcess;
use RZP\Exception\BadRequestException;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Workflow\Service\EntityMap;
use RZP\Models\Workflow\PayoutAmountRules;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt\Initiator;
use RZP\Jobs\PayoutPostCreateProcessLowPriority;
use RZP\Models\PayoutMeta\Core as PayoutMetaCore;
use RZP\Models\Payout\PayoutsIntermediateTransactions;
use RZP\Models\BankingAccountStatement\Details as BASD;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Metric as FundTransferMetric;
use RZP\Models\PayoutsDetails\Core as PayoutsDetailsCore;
use RZP\Models\PayoutsDetails\Utils as PayoutsDetailsUtils;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Models\PayoutsDetails\Entity as PayoutsDetailsEntity;
use RZP\Models\Workflow\Action\Checker\Entity as ActionChecker;
use RZP\Models\Workflow\Service\Client as WorkflowServiceClient;
use RZP\Models\PayoutsStatusDetails\Core as PayoutsStatusDetailsCore;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;


/**
 * Payouts base where we will have a generic flow for the customer/merchants payouts.
 * Class Base
 * @package RZP\Models\Payout\Processor
 */
class Base extends BaseCore
{
    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Batch\Entity
     */
    protected $batch;

    /**
     * @var string
     */
    protected $batchId;

    /**
     * @var Customer\Entity
     */
    protected $customer;

    /**
     * Method by which the payout will be made.
     * @var string
     */
    protected $method;

    /**
     * @var int
     */
    protected $tax = 0;

    /**
     * @var int
     */
    protected $fees = 0;

    /**
     * @var Balance\Entity
     */
    protected $balance;

    /**
     * @var FundAccount\Entity
     */
    protected $fundAccount;

    /**
     * @var bool
     */
    protected $workflowActivated = false;

    /**
     * @var bool
     */
    protected $isInternal = false;

    /**
     * @var BankAccount\Entity|Vpa\Entity|Card\Entity|WalletAccount\Entity
     */
    protected $fundTransferDestination;

    /**
     * @var string
     */
    protected $workflowFeature = null;

    /**
     * @var bool
     */
    protected $isPayoutServiceEnabled = false;

    /**
     * @var bool
     */
    protected $isWorkflowEnabled = false;

    const KEY_SUFFIX = '_payout_workflow';

    /**
     * @var PayoutServiceCreate
     */
    protected $payoutCreateServiceClient;

    /**
     * @var int
     */
    protected $payoutServiceMutexTTL = 120;

    // Payout Service Mutex Keys
    const FTA_CREATION_PAYOUT_SERVICE    = 'fta_creation_payout_service_';
    const LEDGER_CREATION_PAYOUT_SERVICE = 'ledger_creation_payout_service_';

    /**
     * @var Mutex
     */
    protected $mutex;

    const ERROR_CODE = 'error_code';

    // FTS timeout is set to 1 sec. This should be utilized on sync calls to FTS.
    const FTS_TRANSFER_TIMEOUT = 1;


    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->payoutCreateServiceClient = $this->app[PayoutServiceCreate::PAYOUT_SERVICE_CREATE];
    }

    /**
     * isInternal is true only when the payout has been internally generated by Razorpay.
     * Currently rzp_fees payouts are the only kind of internal payouts
     *
     * @param array $input
     *
     * @return Payout\Entity
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createPayout(array $input): Payout\Entity
    {
        $this->setPayoutBalance($input);

        $this->preValidations();

        $skipWorkflow = null;

        if (array_key_exists(Payout\Entity::SKIP_WORKFLOW, $input) === true)
        {
            (new Payout\Validator)->setStrictFalse()
                ->validateInput('skip_workflow', $input);

            $skipWorkflow = isset($input[Entity::SKIP_WORKFLOW]) ? boolval($input[Entity::SKIP_WORKFLOW]) : null;
        }

        // Workflow can be enabled for internal contacts by passing enable_workflow_for_internal_contact field in input.
        $enableWorkflowForInternalContact = false;

        if (array_key_exists(Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT, $input))
        {
            $enableWorkflowForInternalContact = filter_var($input[Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT], FILTER_VALIDATE_BOOLEAN);
        }

        $this->isWorkflowEnabled = $this->isWorkflowApplicable($skipWorkflow, $enableWorkflowForInternalContact);

        // Todo:: To use this parameter in PS for Scrooge FTA Deprecation
        unset($input[Entity::PG_MERCHANT_ID]);

        $payoutViaMicroservice = $this->createPayoutViaMicroservice($input);

        unset($input[Entity::SKIP_WORKFLOW]);

        unset($input[Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT]);

        if (is_null($payoutViaMicroservice) === false)
        {
            return $payoutViaMicroservice;
        }

        /** @var Payout\Entity $payout */
        $payout = $this->repo->transaction(function () use ($input, $skipWorkflow)
        {
            $payout = $this->handleWorkflowsIfApplicable(function() use ($input)
            {
                return $this->createPayoutEntity($input);
            }, $input);

            $sourceDetails = $payout->getInputSourceDetails();

            $this->isPayoutInitiatedByPartner($payout);

            if ($this->workflowActivated === true)
            {
                if (empty($sourceDetails) === false)
                {
                    $this->processSourceDetails($sourceDetails, $payout);
                }

                return $payout;
            }

            $this->schedulePayoutIfApplicable($payout);

            if ($payout->isStatusScheduled() === true)
            {
                if (empty($sourceDetails) === false)
                {
                    $this->processSourceDetails($sourceDetails, $payout);
                }

                return $payout;
            }

            if ($payout->shouldDelayInitiationForBatchPayout() === true)
            {
                $payout->setStatus(Status::BATCH_SUBMITTED);

                $this->repo->saveOrFail($payout);

                $this->trace->info(
                    TraceCode::PAYOUT_STATUS_CHANGED_TO_BATCH_SUBMITTED,
                    [
                        'payout_id'     => $payout->getId(),
                        'merchant_id'   => $payout->getMerchantId(),
                        'batch_id'      => $payout->getBatchId(),
                    ]);

                return $payout;
            }

            if ($payout->getQueuePayoutCreateRequest() === true)
            {
                $this->dispatchForPreCreatedPayouts($payout);

                $payout->setStatus(Status::CREATE_REQUEST_SUBMITTED);

                if (empty($sourceDetails) === false)
                {
                    $this->processSourceDetails($sourceDetails, $payout);
                }

                $this->repo->saveOrFail($payout);

                return $payout;
            }

            // After the current transaction closes, a sync call to FTS is made for payouts with this flag set.
            // Currently we make sync calls for specific flow only i.e. create fund account payout.
            if (($payout->hasFundAccount() === true) and
                ($payout->hasCustomer() === false) and
                ($payout->isBalanceTypeBanking() === true) and
                ($this->isPayoutToFtsASyncModeEnabled($payout) === false))
            {
                // By setting this flag we can skip sending the request to queue and making a sync call.
                $payout->setSyncFtsFundTransferFlag(true);
            }

            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                           $payout,
                                                           $this->mode,
                                                           $this->fundTransferDestination);

            $downstreamProcessor->process();

            if (empty($payout->getStatus()) === true)
            {
                $payout->setStatus(Status::CREATED);
            }

            if (empty($sourceDetails) === false)
            {
                $this->processSourceDetails($sourceDetails, $payout);
            }

            $this->repo->saveOrFail($payout);

            $this->trace->info(
                TraceCode::PAYOUT_CREATED,
                [
                    'input'       => $input,
                    'payout'      => $payout->toArray(),
                ]);

            return $payout;
        });

        if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
            ($payout->isStatusCreated() === true))
        {
            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                           $payout,
                                                           $this->mode,
                                                           $this->fundTransferDestination);

            // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
            // the function processPayoutThroughLedger() only exists in this class
            $downstreamProcessor->processPayoutThroughLedger();
        }

        (new Payout\Core)->processLedgerPayout($payout);

        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->makeSyncFtsFundTransfer() === true))
        {
            $isFts = false;

            $fta = $payout->getFta();

            // fta can be null in some cases like queued payout of CA, on hold payouts.
            if ($fta !== null)
            {
                $isFts = $fta->getIsFts();
            }

            if ($isFts === true)
            {
                $this->syncFTSFundTransfer($payout);
            }
        }

        //TODO: Handle for ledger failures
        $this->fireEventForPayoutStatus($payout);

        return $payout;
    }

    /**
     * Creates a payout entity without any downstream processing (FTA creation, FTS transfer, etc)
     *
     * @throws BadRequestException
     */
    public function createPayoutEntityWithoutDownstreamProcessing(array $input): Payout\Entity
    {
        $this->setPayoutBalance($input);

        if (Payout\Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa($this->balance, $this->merchant) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ENABLED_FOR_2FA_PAYOUT,
                null,
                null,
                'merchant is not enabled for ICICI 2FA payout flow'
            );
        }

        $this->preValidations();

        if ($this->isTestMode() === true)
        {
            $this->isWorkflowEnabled = false;
        }
        else
        {
            // Workflow is enabled by default for this flow
            $this->isWorkflowEnabled = true;
        }

        /** @var Payout\Entity $payout */
        $payout = $this->repo->transaction(function () use ($input)
        {
            $payout = $this->handleWorkflowsIfApplicable(function() use ($input)
            {
                return $this->createPayoutEntity($input);
            }, $input);

            $sourceDetails = $payout->getInputSourceDetails();

            $this->isPayoutInitiatedByPartner($payout);

            if ($this->workflowActivated === true)
            {
                if (empty($sourceDetails) === false)
                {
                    $this->processSourceDetails($sourceDetails, $payout);
                }

                return $payout;
            }

            if ($this->mode == MODE::TEST)
            {
                // For test mode, workflow will fail, hence set to pending here
                $payout->setStatus(Status::PENDING);
            }

            if (empty($sourceDetails) === false)
            {
                $this->processSourceDetails($sourceDetails, $payout);
            }

            $this->repo->saveOrFail($payout);

            $this->trace->info(
                TraceCode::PAYOUT_CREATED,
                [
                    'input'       => $input,
                    'payout'      => $payout->toArray(),
                ]);

            return $payout;
        });

        return $payout;
    }

    /**
     * NOT SUPPORTED: Workflow, Scheduled Payouts, Partner payouts, Payouts via Apps, Batch Payouts, Payout Microservice
     *
     * @param array               $input
     * @param Balance\Entity|null $balance
     *
     * @param array               $payoutMetadata
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function createPayoutForCompositePayoutFlow(array $input, Balance\Entity $balance, array $payoutMetadata = []): Entity
    {
        $this->setPayoutBalance($input, $balance);

        $this->preValidations();

        /** @var Payout\Entity $payout */
        $payout = $this->repo->transaction(function () use ($input, $payoutMetadata)
        {
            $payout =  $this->createPayoutEntityForNewCompositePayoutFlow($input, true, $payoutMetadata);

            if ($payout->getQueuePayoutCreateRequest() === true)
            {
                $payout->setStatus(Status::CREATE_REQUEST_SUBMITTED);

                $this->repo->payout->saveOrFail($payout);

                $this->dispatchForPreCreatedPayouts($payout);

                return $payout;
            }

            // After the current transaction closes, a sync call to FTS is made for payouts with this flag set.
            // Currently we make sync calls for specific flow only i.e. create fund account payout.
            if ($this->isPayoutToFtsASyncModeEnabled($payout) === false)
            {
                // By setting this flag we can skip sending the request to queue and making a sync call.
                $payout->setSyncFtsFundTransferFlag(true);
            }

            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                           $payout,
                                                           $this->mode,
                                                           $this->fundTransferDestination);

            $downstreamProcessor->process();

            if (empty($payout->getStatus()) === true)
            {
                $payout->setStatus(Status::CREATED);
            }

            $this->repo->payout->saveOrFail($payout);

            return $payout;
        });

        if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
            ($payout->isStatusCreated() === true))
        {
            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                           $payout,
                                                           $this->mode,
                                                           $this->fundTransferDestination);

            // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
            // the function processPayoutThroughLedger() only exists in this class
            $downstreamProcessor->processPayoutThroughLedger();
        }

        $this->trace->info(
            TraceCode::PAYOUT_CREATED_FOR_COMPOSITE_PAYOUT,
            [
                'input'  => $input,
                'payout' => $payout->toArrayPublic(),
            ]);

        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->makeSyncFtsFundTransfer() === true))
        {
            $isFts = false;

            $fta = $payout->getFta();

            // fta can be null in some cases like queued payout of CA, on hold payouts.
            if ($fta !== null)
            {
                $isFts = $fta->getIsFts();
            }

            if ($isFts === true)
            {
                $this->syncFTSFundTransfer($payout);
            }
        }

        $this->fireEventForPayoutStatus($payout);

        return $payout;
    }

    // Default would be a sync call to FTS.
    // In case we don't want that to happen, we'll have to enable the async mode
    protected function isPayoutToFtsASyncModeEnabled(Entity $payout)
    {
        // this flag being true would mean that we wish to hit FTS in async mode
        $asyncEnabled = $payout->merchant->isFeatureEnabled(Feature::PAYOUT_ASYNC_FTS_TRANSFER);

        $this->trace->info(
            TraceCode::ASYNC_FTS_FUND_TRANSFER_ENABLED,
            [
                'payout_id'   => $payout->getId(),
                'merchant_id' => $payout->merchant->getId(),
                'enabled'     => $asyncEnabled,
            ]);

        return $asyncEnabled;
    }

    public function syncFTSFundTransfer(Entity $payout, string $otp = null)
    {
        $fta = $payout->getFta();

        if ($fta === null)
        {
            //This is added for retry transfer with otp scenario for ICICI ca.
            // Since fta is already created in first try second time $this->fta will not be found.
            if(empty($payout->fundTransferAttempts->first()) === false)
            {
                $fta = $payout->fundTransferAttempts->first();
            }
            else
            {
                return;
            }
        }

        try
        {
            assertTrue($this->repo->isTransactionActive() === false);

            $this->trace->info(TraceCode::SYNC_FTS_FUND_TRANSFER_INIT,
                               [
                                   'payout_id'    => $payout->getId(),
                                   'fta_id'       => $fta->getId(),
                                   'merchant_id'  => $payout->getMerchantId(),
                                   'current_time' => microtime(true),
                                   'balance_id'   => $payout->getBalanceId(),
                               ]);

            /** @var \RZP\Services\FTS\FundTransfer $transferService */
            $transferService = App::getFacadeRoot()['fts_fund_transfer'];

            $transferService->initializeWithFta($fta);

            list($initiateTransfers, $reason) = $transferService->shouldAllowTransfersViaFts();

            if ($initiateTransfers === false)
            {
                $addedInitiateAt = $transferService->addInitiateAtIfRequired();

                if ($addedInitiateAt === false) {

                    $data = [
                        'fta_id' => $fta->getId(),
                        'reason' => $reason,
                    ];

                    $this->trace->info(TraceCode::FTS_FUND_TRANSFER_NOT_ALLOWED, $data);

                    throw new Exception\LogicException('fts fund transfer not allowed', null, $data);
                }
            }

            $transferService->setRequestTimeout(self::FTS_TRANSFER_TIMEOUT);

            $ftsResponse = $transferService->requestFundTransfer($otp);

            $this->trace->info(
                TraceCode::SYNC_FTS_FUND_TRANSFER_COMPLETE,
                [
                    'payout_id'    => $payout->getId(),
                    'fta_id'       => $fta->getId(),
                    'merchant_id'  => $payout->getMerchantId(),
                    'response'     => $ftsResponse,
                    'current_time' => microtime(true),
                    'balance_id'   => $payout->getBalanceId(),
                ]);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::SYNC_FTA_DISPATCH_FOR_MERCHANT_FAILED,
                [
                    'payout_id'   => $payout->getId(),
                    'fta_id'      => $fta->getId(),
                    'merchant_id' => $payout->getMerchantId(),
                    'balance_id'  => $payout->getBalanceId(),
                ]);

            if ($exception->getMessage() === SubVirtualAccount\Core::SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR)
            {
                $this->trace->info(TraceCode::ASYNC_FTS_DISPATCH_SKIPPED,
                [
                    'reason' => SubVirtualAccount\Core::SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR
                ]);

                return;
            }
            // If any exception is raised while making sync call, we push the fta to queue as fall back.
            (new Initiator)->sendFTSFundTransferRequest($fta, $otp);
        }
    }

    public function processQueuedPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);

        $feeType = $payout->getExpectedFeeType();

        try
        {
            $payout = $this->repo->transaction(
                function() use ($payout)
                {
                    // TODO: Later, we will have to handle active / inactive stuff also here.
                    // Refer the function `fetchAndAssociatePayoutAccount`
                    // Also, this will have to be fixed for MerchantPayout since there the fundTransferDestination
                    // is merchant's bank account.
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                   $payout,
                                                                   $this->mode,
                                                                   $this->fundTransferDestination);

                    //
                    // Ensure that the queued flag in the payout entity is not set.
                    // If it is set, it's going to cause issues since the downstream processor
                    // doesn't throw an error on insufficient funds if queued flag is set.
                    // If it doesn't throw an error, we'll end up marking it created without actually
                    // creating any transaction or FTA.
                    //
                    $downstreamProcessor->process();

                    $payout->setStatus(Payout\Status::CREATED);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::QUEUED_PAYOUT_CREATED,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }
        catch (\Throwable $throwable)
        {
            $balanceId = $payout->getBalanceId();

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->traceException(
                $throwable,
                Logger::CRITICAL,
                TraceCode::QUEUED_PAYOUT_PROCESS_EXCEPTION,
                [
                    'payout_id'            => $payout->getId(),
                    'balance_id'           => $balanceId,
                    'balance_channel'      => optional($payout->balance)->getChannel() ?? null,
                    'balance_type'         => optional($payout->balance)->getType() ?? null,
                    'balance_account_type' => optional($payout->balance)->getAccountType() ?? null
                ]
            );

            throw $throwable;
        }


        $this->fireEventForPayoutStatus($payout);

        //
        // This needs to be done only for fund_account type and not for others.
        // We need to figure out at this stage what type of payout are we processing in queue.
        // Since, currently, we only do fund_account, we are not handling it. Once we start
        // processing queued payouts for other types also, this needs to be changed.
        //
        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->balance->getAccountType() !== Merchant\Balance\AccountType::DIRECT) and
            ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

        return $payout;
    }

    public function processOnHoldPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);

        $feeType = $payout->getExpectedFeeType();

        try
        {
            $payout = $this->repo->transaction(
                function () use ($payout)
                {
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                        $payout,
                        $this->mode,
                        $this->fundTransferDestination);

                    $payoutQueueFlagDetails = $payout->payoutsDetails;

                    //this case is specifically for queued after on_hold state,
                    // context of queue_if_low_balance is stored in payouts_details table
                    // and the behaviour on insufficient funds is driven by this
                    if ($payoutQueueFlagDetails != null and
                        $payoutQueueFlagDetails->getQueueIfLowBalanceFlag() === true)
                    {
                        $payout->setQueueFlag(true);
                    }
                    try
                    {
                        $downstreamProcessor->process();
                    }
                    catch (\Exception $ex)
                    {
                        $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

                        // If the error is due to insufficient balance, we shall mark the payout as failed.
                        // Earlier, payout creation itself would have failed. Now we are saving the payout regardless,
                        // Here we will check if queue_if_low_balance flag is true we will mark payout as queued else
                        //we will mark it failed.
                        if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
                        {
                            $payout->setStatus(Status::FAILED);

                            $payout->setFailureReason('Insufficient balance to process payout');

                            $payout->setStatusCode($insufficientFundsErrorCode);

                            $this->repo->saveOrFail($payout);

                            $this->trace->info(
                                TraceCode::ON_HOLD_PAYOUT_FAILED,
                                [
                                    'payout_id'      => $payout->getId(),
                                    'transaction_id' => $payout->getTransactionId(),
                                    'payout_status'  => $payout->getStatus(),
                                    'failure_reason' => $payout->getFailureReason(),
                                ]);

                            return $payout;
                        }
                        else
                        {
                            throw $ex;
                        }
                    }

                    if($payout->isStatusQueued() === true)
                    {
                        $this->repo->saveOrFail($payout);

                        return $payout;
                    }

                    $payout->setStatus(Payout\Status::CREATED);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::ON_HOLD_PAYOUT_MOVED_TO_CREATED_STATE,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }
        catch (\Throwable $throwable)
        {
            $balanceId = $payout->getBalanceId();

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->info(
                TraceCode::ON_HOLD_PAYOUT_PROCESS_EXCEPTION,
                [
                    'payout_id'            => $payout->getId(),
                    'balance_id'           => optional($payout->balance)->getId() ?? null,
                    'balance_type'         => optional($payout->balance)->getType() ?? null,
                    'balance_channel'      => optional($payout->balance)->getChannel() ?? null,
                    'balance_account_type' => optional($payout->balance)->getAccountType() ?? null,
                ]
            );

            throw $throwable;
        }

        // We only have to send mail/webhook if the payout fails.
        // We have already sent the initiated mail/webhook when we marked the payout as batch_submitted
        if ($payout->getStatus() === Status::FAILED)
        {
            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.failed', [$payout]);
        }

        // This needs to be done only for fund_account type and not for others.
        // We need to figure out at this stage what type of payout are we processing in batch_submitted.
        // Since, currently, we only do fund_account, we are not handling it. Once we start
        // processing queued payouts for other types also, this needs to be changed.

        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->getBalanceAccountType() === AccountType::SHARED) and
            ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

        return $payout;
    }

    public function processBatchSubmittedPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);

        $feeType = $payout->getExpectedFeeType();

        try
        {
            $payout = $this->repo->transaction(
                function() use ($payout)
                {
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                   $payout,
                                                                   $this->mode,
                                                                   $this->fundTransferDestination);

                    try
                    {
                        $downstreamProcessor->process();
                    }
                    catch (\Exception $ex)
                    {
                        $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

                        //
                        // If the error is due to insufficient balance, we shall mark the payout as failed.
                        // Earlier, payout creation itself would have failed. Now we are saving the payout regardless,
                        // hence marking it as failed at this point.
                        //
                        if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
                        {
                            $payout->setStatus(Status::FAILED);

                            $payout->setFailureReason('Insufficient balance to process payout');

                            $payout->setStatusCode($insufficientFundsErrorCode);

                            $this->repo->saveOrFail($payout);

                            $this->trace->info(
                                TraceCode::BATCH_SUBMITTED_PAYOUT_FAILED,
                                [
                                    'payout_id'      => $payout->getId(),
                                    'transaction_id' => $payout->getTransactionId(),
                                    'payout_status'  => $payout->getStatus(),
                                ]);

                            return $payout;
                        }
                        else
                        {
                            throw $ex;
                        }
                    }
                    /* Downstream processor can set the status to on_hold in some cases (bene_bank_down)
                       If set, we want the payout to remain in on_hold so it can be processed separately.
                       Hence, payout status is set to created only if it's not already queued.
                    */
                    if($payout->isStatusOnHold() === true)
                    {
                        $this->repo->saveOrFail($payout);

                        return $payout;
                    }

                    $payout->setStatus(Payout\Status::CREATED);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::BATCH_SUBMITTED_PAYOUT_CREATED,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }

        catch (\Throwable $throwable)
        {
            $balanceId = $payout->getBalanceId();

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->traceException(
                $throwable,
                Logger::CRITICAL,
                TraceCode::BATCH_SUBMITTED_PAYOUT_PROCESS_EXCEPTION,
                [
                    'payout_id'            => $payout->getId(),
                    'balance_id'           => $balanceId,
                    'balance_channel'      => optional($payout->balance)->getChannel() ?? null,
                    'balance_type'         => optional($payout->balance)->getType() ?? null,
                    'balance_account_type' => optional($payout->balance)->getAccountType() ?? null
                ]
            );

            throw $throwable;
        }

        // We only have to send mail/webhook if the payout fails.
        // We have already sent the initiated mail/webhook when we marked the payout as batch_submitted
        if ($payout->getStatus() === Status::FAILED)
        {
            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.failed', [$payout]);
        }

        //
        // This needs to be done only for fund_account type and not for others.
        // We need to figure out at this stage what type of payout are we processing in batch_submitted.
        // Since, currently, we only do fund_account, we are not handling it. Once we start
        // processing queued payouts for other types also, this needs to be changed.
        //
        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->getBalanceAccountType() === AccountType::SHARED) and
            ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

        return $payout;
    }

    public function processPayoutPostCreate(Payout\Entity $payout, bool $queueFlag): Payout\Entity
    {
        //TODO: Need to remove the below flag once all merchants are off-boarded into ingress and egress flags
        $highTPSCompositePayoutFlag = $payout->merchant->isFeatureEnabled(Features::HIGH_TPS_COMPOSITE_PAYOUT);
        $highTPSPayoutEgressFlag    = $payout->merchant->isFeatureEnabled(Features::HIGH_TPS_PAYOUT_EGRESS);
        $highTpsPayoutIngressFlag   = $payout->merchant->isFeatureEnabled(Features::HIGH_TPS_PAYOUT_INGRESS);

        if (($highTPSCompositePayoutFlag === false) and
            ($highTPSPayoutEgressFlag === false) and
            ($highTpsPayoutIngressFlag === false))
        {
            $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);
        }

        $feeType = $payout->getExpectedFeeType();

        $payout->setQueueFlag($queueFlag);

        if (($payout->hasFundAccount() === true) and
            ($payout->hasCustomer() === false) and
            ($payout->isBalanceTypeBanking() === true) and
            ($this->isPayoutToFtsASyncModeEnabled($payout) === false) and
            ($payout->getIsPayoutService() === false))
        {
            // By setting this flag we can skip sending the request to queue and making a sync call.
            $payout->setSyncFtsFundTransferFlag(true);
        }

        if (($highTPSCompositePayoutFlag === true) or ($highTPSPayoutEgressFlag === true))
        {
            /** @var PayoutsIntermediateTransactions\Entity $intermediateTxn */
            $intermediateTxn = (new PayoutsIntermediateTransactions\Core)
                                ->fetchIntermediateTransactionForAGivenPayoutId($payout->getId());

            $payout->setBalancePreDeductedFlag(true);

            if ($intermediateTxn !== null)
            {
                // This will mean that the intermediate payout transaction is in a terminal state
                // and hence we don't need to do any processing on it.
                if ($intermediateTxn->getStatus() !== PayoutsIntermediateTransactions\Status::PENDING)
                {
                    return $payout;
                }
            }

            if ($intermediateTxn === null)
            {
                [$fees, $tax, $pricingRuleId] = $this->setFeeAndTaxForHighTpsCompositePayouts($payout);

                $startTime = microtime(true);

                try
                {
                    [$payout, $intermediateTxn] = $this->repo->transaction(
                        function() use ($payout, $fees, $tax) {
                            $this->deductBalancePreProcessing($payout, $fees, $tax);

                            $transactionId        = UniqueIdEntity::generateUniqueId();
                            $transactionCreatedAt = Carbon::now(Timezone::IST)->getTimestamp();
                            $closingBalance       = $payout->balance->getBalance();

                            $payout->setTransactionIdWhenBalancePreDeducted($transactionId);
                            $payout->setTransactionCreatedAtWhenBalancePreDeducted($transactionCreatedAt);
                            $payout->setClosingBalanceWhenBalancePreDeducted($closingBalance);

                            $inputForIntermediateTxn = [
                                PayoutsIntermediateTransactions\Entity::PAYOUT_ID              => $payout->getId(),
                                PayoutsIntermediateTransactions\Entity::AMOUNT                 => $payout->getAmount() + $fees,
                                PayoutsIntermediateTransactions\Entity::CLOSING_BALANCE        => $closingBalance,
                                PayoutsIntermediateTransactions\Entity::TRANSACTION_ID         => $transactionId,
                                PayoutsIntermediateTransactions\Entity::TRANSACTION_CREATED_AT => $transactionCreatedAt
                            ];

                            $intermediateTxn = (new PayoutsIntermediateTransactions\Core)->create($inputForIntermediateTxn);

                            return [$payout, $intermediateTxn];
                        });

                    $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
                        'step'       => 'pre_deduct_balance_lock_time_taken',
                        'time_taken' => (microtime(true) - $startTime) * 1000,
                    ]);

                    // need to set this here.
                    // In case if db txn 2 fails, for creating reversal in catch block we will need fee and tax
                    $payout->setFees($fees);
                    $payout->setTax($tax);

                    $this->repo->payout->saveOrFail($payout);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Logger::CRITICAL,
                        TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED,
                        [
                            'payout_id' => $payout->getId(),
                        ]);

                    if ($ex->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING)
                    {
                        $payout->setFees(0);

                        $payout->setTax(0);

                        unset($payout[Entity::PRICING_RULE_ID]);

                        if ($payout->toBeQueued() === true)
                        {
                            $payout->setStatus(Status::QUEUED);

                            $payout->setQueuedReason(QueuedReasons::LOW_BALANCE);

                            (new PayoutsStatusDetailsCore())->create($payout);

                            $this->app->events->dispatch('api.payout.queued', [$payout]);
                        }
                        else
                        {
                            $payout->setFailureReason('Insufficient balance to process payout');

                            $payout->setStatusCode(ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING);

                            $payout->setStatus(Status::FAILED);

                            (new PayoutsStatusDetailsCore())->create($payout);

                            $this->app->events->dispatch('api.payout.failed', [$payout]);
                        }

                        $this->repo->payout->saveOrFail($payout);

                        return $payout;
                    }

                    throw $ex;
                }

            }
        }

        try
        {
            $payout = $this->repo->transaction(
                function() use ($payout, $highTPSCompositePayoutFlag)
                {
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                   $payout,
                                                                   $this->mode,
                                                                   $this->fundTransferDestination);

                    $downstreamProcessor->process();

                    if ($payout->getStatus() === Status::CREATE_REQUEST_SUBMITTED)
                    {
                        $payout->setStatus(Status::CREATED);
                    }

                    if (($payout->getStatus() === Status::SCHEDULED) or
                        ($payout->getStatus() === Status::QUEUED) or
                        ($payout->getStatus() === Status::PENDING) or
                        ($payout->getStatus() === Status::ON_HOLD))
                    {
                        if (($payout->getTransactionId() !== null) and
                            ($this->app['basicauth']->isPayoutService() === true))
                        {
                            $payout->setStatus(Status::CREATED);
                        }
                    }

                    $this->repo->payout->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::PAYOUT_CREATED,
                        [
                            'payout_id'      => $payout->getId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED,
                [
                    'payout_id'      => $payout->getId(),
                    'payout_status'  => $payout->getStatus(),
                ]);

            $balanceId = $payout->getBalanceId();

            if (($highTPSCompositePayoutFlag === true) or ($highTPSPayoutEgressFlag === true))
            {
                (new PayoutsIntermediateTransactions\Helper)->markIntermediateTransactionReversedAndIncrementBalance($payout);
            }
            else if ($highTpsPayoutIngressFlag === false)
            {
                (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);
            }

            $payout->reload();

            if (($highTPSCompositePayoutFlag === false or $highTPSPayoutEgressFlag === false) and
                Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === false)
            {
                if ($ex->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING)
                {
                    $payout->setFailureReason('Insufficient balance to process payout');

                    $payout->setStatusCode(ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING);

                    $payout->setStatus(Status::FAILED);
                }
                else
                {
                    $alertData = [
                        'payout_id' => $payout->getId(),
                    ];

                    (new SlackNotification)->send(
                        'Payout with intermediate state failed due to some other error than balance failure',
                        $alertData,
                        $ex,
                        1,
                        'x-payouts-core-alerts');

                    $payout->setFailureReason('Payout failed. Contact support for help');

                    $payout->setStatus(Status::FAILED);

                    $payout->setStatusCode(ErrorCode::BAD_REQUEST_PAYOUT_FAILED_UNKNOWN_ERROR);
                }

                $this->repo->payout->saveOrFail($payout);
            }
            else
            {
                if ($ex->getError()->getInternalErrorCode() !== ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING)
                {
                    $alertData = [
                        'payout_id' => $payout->getId(),
                    ];

                    (new SlackNotification)->send(
                        'Payout with intermediate state failed due to some other error than balance failure',
                        $alertData,
                        $ex,
                        1,
                        'x-payouts-core-alerts');
                }
            }
        }


        if ($payout->isBalancePreDeducted() === true)
        {
            if ($intermediateTxn->getStatus() === PayoutsIntermediateTransactions\Status::PENDING)
            {
                $this->repo->transaction(function() use($payout, $intermediateTxn){

                    (new PayoutsIntermediateTransactions\Core)->markIntermediateTransactionCompleted($intermediateTxn);

                    // associate balance id with payout txn. this is not being done in flow when balance is pre deducted
                    $payoutTxn = $payout->transaction;

                    $this->repo->transaction->reload($payoutTxn);

                    $payoutTxn->accountBalance()->associate($payout->balance);

                    $this->repo->transaction->saveOrFail($payoutTxn);
                });
            }
        }

        (new Payout\Core)->processLedgerPayout($payout);

        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->makeSyncFtsFundTransfer() === true))
        {
            $isFts = false;

            $fta = $payout->getFta();

            // fta can be null in some cases like queued payout of CA, on hold payouts.
            if ($fta !== null)
            {
                $isFts = $fta->getIsFts();
            }

            if ($isFts === true)
            {
                $this->syncFTSFundTransfer($payout);
            }
        }

        if ($payout->getIsPayoutService() === false)
        {
            // We only have to send mail/webhook if the payout fails.
            // In ledger reverse shadow it is already dispatched when handling ledger failure
            if (($payout->getStatus() === Status::FAILED) and
                ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
            {
                (new PayoutsStatusDetailsCore())->create($payout);

                $this->app->events->dispatch('api.payout.failed', [$payout]);
            }
            else
            {
                if (($highTPSCompositePayoutFlag === false) or ($highTPSPayoutEgressFlag === false))
                {
                    $payoutType = $this->getPayoutType();

                    $processor = (new Payout\Core)->getProcessor($payoutType);

                    $processor->fireEventForPayoutStatus($payout);

                    if (($payout->isStatusBeforeCreate() === false) and
                        ($payout->getBalanceAccountType() === AccountType::SHARED) and
                        ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
                    {
                        (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
                    }
                }
            }
        }
        else if ($payout->getIsPayoutService() === true)
        {
            //// We only have to send mail/webhook if the payout fails.
            //if ($payout->getStatus() === Status::FAILED)
            //{
            //    (new PayoutsStatusDetailsCore())->create($payout);
            //}
        }

        return $payout;
    }

    protected function setFeeAndTaxForHighTpsCompositePayouts($payout)
    {
        list($fees, $tax, $pricingRuleId) = $this->calculateFeesAndTaxForHighTpsCompositePayouts($payout);

        if (empty($pricingRuleId) === true)
        {
            throw new LogicException('No Pricing Rule ID set for payout: ' . $payout->getId());
        }

        $payout->setFees($fees);

        $payout->setTax($tax);

        $payout->setPricingRuleId($pricingRuleId);

        return [$fees, $tax, $pricingRuleId];
    }

    protected function calculateFeesAndTaxForHighTpsCompositePayouts(Entity $payout)
    {
        list($fees, $tax, $feesSplit) = (new Pricing\PayoutFee)->calculateMerchantFees($payout);

        $feesSplitData = $feesSplit->toArray();

        foreach ($feesSplitData as $feesSplit)
        {
            // Set pricingRuleId from the feesSplit (there are two entries and at least one has pricingRuleId)
            if (empty($feesSplit[Entity::PRICING_RULE_ID]) === false)
            {
                $pricingRuleId = $feesSplit[Entity::PRICING_RULE_ID];
            }
        }

        return [$fees, $tax, $pricingRuleId];
    }

    protected function deductBalancePreProcessing(Payout\Entity $payout, $fees, $tax)
    {
        $payout->setBalancePreDeductedFlag(true);

        (new Transaction\Processor\Payout($payout))->preDeductBalanceForPayout($fees, $tax);
    }

    public function processScheduledPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);

        $feeType = $payout->getExpectedFeeType();

        try
        {
            $payout = $this->repo->transaction(
                function() use ($payout)
                {
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                   $payout,
                                                                   $this->mode,
                                                                   $this->fundTransferDestination);

                    //
                    // Ensure that the queued flag in the payout entity is not set.
                    // We want scheduled payouts to fail in case there isn't enough balance
                    //
                    try
                    {
                        $downstreamProcessor->process();
                    }
                    catch (\Exception $ex)
                    {
                        $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

                        //
                        // If the error is due to insufficient balance, we shall mark the payout as failed.
                        //
                        if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
                        {
                            $payout->setStatus(Status::FAILED);

                            $payout->setFailureReason('Insufficient balance to process payout');

                            $payout->setStatusCode($insufficientFundsErrorCode);

                            $this->repo->saveOrFail($payout);

                            $this->trace->info(
                                TraceCode::SCHEDULED_PAYOUT_FAILED,
                                [
                                    'payout_id'      => $payout->getId(),
                                    'transaction_id' => $payout->getTransactionId(),
                                    'payout_status'  => $payout->getStatus(),
                                ]);

                            // We have only implemented the email function. No SMS will be sent.
                            (new Notifications\Factory)->getNotifier(Notifications\Type::PAYOUT_FAILED, $payout)
                                                       ->notify();

                            return $payout;
                        }
                        else
                        {
                            throw $ex;
                        }
                    }

                    if ($payout->isStatusOnHold() === true)
                    {
                        $this->repo->saveOrFail($payout);

                        return $payout;
                    }
                    $payout->setStatus(Payout\Status::CREATED);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::SCHEDULED_PAYOUT_CREATED,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }

        catch (\Throwable $throwable)
        {
            $balanceId = $payout->getBalanceId();

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->traceException(
                $throwable,
                Logger::CRITICAL,
                TraceCode::SCHEDULED_PAYOUT_PROCESS_EXCEPTION,
                [
                    'payout_id'            => $payout->getId(),
                    'balance_id'           => $balanceId,
                    'balance_type'         => optional($payout->balance)->getType() ?? null,
                    'balance_channel'      => optional($payout->balance)->getChannel() ?? null,
                    'balance_account_type' => optional($payout->balance)->getAccountType() ?? null
                ]
            );

            throw $throwable;
        }

        $this->fireEventForPayoutStatus($payout);

        //
        // Refer to similar comment on processQueuedPayout.
        // This needs to change if we want to handle for any other type apart from Fund Account Payout
        //
        // We can now also mark the payout as `failed`, `rejected` if the merchant does not have enough balance or if
        // the payout hasn't been approved, in such a case,we wouldn't want to fire the transaction created webhook,
        // since no transaction was created.
        //
        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->getBalanceAccountType() === AccountType::SHARED) and
            ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

        return $payout;
    }

    public function processIciciCAPendingPayout(Entity $payout, array $input)
    {
        try
        {
            /** @var Payout\Entity $payout */
            $payout = $this->repo->transaction(
                function() use ($payout, $input)
                {
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    $this->saveUserCommentInSettingsEntity($payout, $input[ActionChecker::USER_COMMENT] ?? '');

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                        $payout,
                        $this->mode,
                        $this->fundTransferDestination);

                    $downstreamProcessor->processIcici2FA();

                    $payout->setStatus(Payout\Status::PENDING_ON_OTP);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::PAYOUT_ICICI_CA_PENDING_PAYOUT_SUBMITTED,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            $this->syncFTSFundTransfer($payout, $input['otp']);
        }
        catch (\Throwable $throwable)
        {
            $this->trace->error(
                TraceCode::PAYOUT_ICICI_CA_PENDING_PAYOUT_PROCESS_FAILED,
                [
                    'payout_id'     => $payout->getId(),
                ]);

            $this->trace->count(Metric::ICICI_2FA_APPROVE_ROUTE_FAILURES_COUNT);

            throw $throwable;
        }

        return $payout;
    }

    public function processPendingPayout(Payout\Entity $payout, bool $queueFlag): Payout\Entity
    {
        $payout = $this->incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts($payout);

        $feeType = $payout->getExpectedFeeType();

        try
        {
            /** @var Payout\Entity $payout */
            $payout = $this->repo->transaction(
                function() use ($payout, $queueFlag)
                {
                    //
                    // TODO: Later, we will have to handle active / inactive stuff also here.
                    // Refer the function `fetchAndAssociatePayoutAccount`
                    //
                    $this->fundTransferDestination = $payout->fundAccount->account;

                    $payoutType = $this->getPayoutType();

                    // We shall check if the payout needs to be scheduled, schedule it and return it from here.
                    // If the payout does not need to be scheduled, then it will get processed normally.
                    $this->schedulePayoutIfApplicable($payout);

                    if ($payout->isStatusScheduled() === true)
                    {
                        return $payout;
                    }

                    if (empty($payout->getBatchId()) === false)
                    {
                        $payout->setStatus(Status::BATCH_SUBMITTED);

                        $this->repo->saveOrFail($payout);

                        $this->trace->info(
                            TraceCode::PENDING_PAYOUT_TO_BATCH_SUBMITTED,
                            [
                                'payout_id'     => $payout->getId(),
                                'merchant_id'   => $payout->getMerchantId(),
                                'batch_id'      => $payout->getBatchId(),
                            ]);

                        return $payout;
                    }

                    $payout->setQueueFlag($queueFlag);

                    $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                   $payout,
                                                                   $this->mode,
                                                                   $this->fundTransferDestination);

                    $downstreamProcessor->process();

                    /*
                     Downstream processor can set the status to queued/on_hold in some cases (low balance,bene_bank_down)
                     If set, we want the payout to remain in queued/on_hold so it can be processed separately.
                     Hence, payout status is set to created only if it's not already queued.
                    */
                    if (($payout->isStatusQueued() === false) and
                        ($payout->isStatusOnHold() === false))
                    {
                        $payout->setStatus(Payout\Status::CREATED);
                    }

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::PENDING_PAYOUT_CREATED,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                        ]);

                    return $payout;
                });

            if ((Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true) and
                ($payout->isStatusCreated() === true))
            {
                $payoutType = $this->getPayoutType();

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->mode,
                                                               $this->fundTransferDestination);

                // Assuming that only DownstreamProcessor\FundAccountPayout\Shared\Base will be used.
                // the function processPayoutThroughLedger() only exists in this class
                $downstreamProcessor->processPayoutThroughLedger();
            }
        }

        catch (\Throwable $throwable)
        {
            $balanceId = $payout->getBalanceId();

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->traceException(
                $throwable,
                Logger::CRITICAL,
                TraceCode::PENDING_PAYOUT_PROCESS_EXCEPTION,
                [
                    'payout_id'            => $payout->getId(),
                    'balance_id'           => $balanceId,
                    'balance_channel'      => optional($payout->balance)->getChannel() ?? null,
                    'balance_type'         => optional($payout->balance)->getType() ?? null,
                    'balance_account_type' => optional($payout->balance)->getAccountType() ?? null
                ]
            );

            throw $throwable;
        }

        $this->fireEventForPayoutStatus($payout);

        if (($payout->isStatusBeforeCreate() === false) and
            ($payout->getBalanceAccountType() === AccountType::SHARED) and
            ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false))
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

        return $payout;
    }

    /**
     * Set the merchant context, always required.
     *
     * @param Merchant\Entity $merchant
     *
     * @return Base
     */
    public function setMerchant(Merchant\Entity $merchant): self
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function setBatch($batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function setInternal(bool $isInternal): self
    {
        $this->isInternal = $isInternal;

        return $this;
    }

    public function setFundAccount(FundAccount\Entity $fundAccount): self
    {
        $this->fundAccount = $fundAccount;

        return $this;
    }

    /**
     * @param callable $createPayoutCallback The callable is expected to create and return a payout entity.
     * @param array $input
     * @return Entity
     * @throws BadRequestException
     * @throws \Exception
     */
    protected function handleWorkflowsIfApplicable(callable $createPayoutCallback, array $input = [])
    {
        if ($this->isWorkflowEnabled === false)
        {
            return $createPayoutCallback();
        }

        //
        // Workflows module works on org and requires org details to be set in basicauth
        // The current flows set and override org details at multiple places. We're setting
        // this here explicitly to avoid bugs and missed flows. Not ideal, but not harmful either.
        //
        app('basicauth')->setOrgDetails($this->merchant->org);

        //
        // Call the create Payout callback that will return a base Payout entity,
        // which we further work with.
        //
        /** @var Payout\Entity $payout */
        $payout = $createPayoutCallback();

        if ($this->isWorkflowServiceEnabled() === true)
        {
            try
            {
                return $this->handleWorkflowCreationWithWorkflowService($payout, $input);
            }
            catch (\Throwable $e)
            {
                // todo: remove this when the system is ramped up to 100%, or some merchant starts using complex WFs

                // Until the system is fully ramped up, if there an issue connecting with workflow service
                // proceed to use the api workflow system

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_WORKFLOW_SERVICE_WORKFLOW_CREATE_FAILED,
                    [
                        'id'    => optional($payout)->getId(),
                        'input' => $input,
                    ]);

                $this->trace->count(Metric::PAYOUT_WORKFLOW_CREATION_FAILED_TOTAL);

                // Remove workflow fallback for self-serve feature
                // TODO: Make this default (removing the API fallback) for all the flows

                $isCacEnabled = $this->merchant->isCACEnabled();

                $isIcici2faEnabled = $this->merchant->isFeatureEnabled(Features::ICICI_2FA);

                $isSSWFEnabled = $this->app['razorx']->getTreatment($this->merchant->getId(),
                        Merchant\RazorxTreatment::RX_SELF_SERVE_WORKFLOW,
                        Mode::LIVE) === 'on';

                if ($isCacEnabled === true || $isSSWFEnabled === true || $isIcici2faEnabled === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE,
                        null,
                        ['payout_id' => optional($payout)->getId()]);
                }
            }
        }

        $amount = $payout->getAmount();

        $payoutAmountRuleBeforeWorkflow = (new PayoutAmountRules\Core)->fetchPayoutAmountRuleForMerchantIfDefined($amount, $this->merchant);

        try
        {
            //
            // Initiate the workflow process. If a workflow is triggered successfully,
            // this function will thrown an EarlyWorkflowResponse exception.
            //
            $this->app['workflow']
                 ->setEntityAndId($payout->getEntity(), $payout->getId())
                 ->setPermission(Permission\Name::CREATE_PAYOUT)
                 ->handle((new \stdClass), $payout);
        }
        catch (Exception\EarlyWorkflowResponse $ex)
        {
            $this->handleEarlyWorkflowResponse($payout, $payoutAmountRuleBeforeWorkflow);
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE,
                null,
                ['payout_id' => optional($payout)->getId()]);
        }

        return $payout;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isWorkflowServiceEnabled(): bool
    {
        $isBlacklistedMerchant = $this->merchant->isFeatureEnabled(Features::BLOCKLIST_FOR_WORKFLOW_SERVICE);

        $this->trace->info(TraceCode::WORKFLOW_SERVICE_RAZOR_X_TREATMENT, [
            'result'        => $isBlacklistedMerchant,
            'merchant_id'   => $this->merchant->getId(),
        ]);

        return $isBlacklistedMerchant === false;
    }

    /**
     * @param Entity $payout
     * @param array $input
     * @return Entity
     */
    protected function handleWorkflowCreationWithWorkflowService(Payout\Entity $payout, array $input = [])
    {
        $isIcici2faEnabled = $this->merchant->isFeatureEnabled(Features::ICICI_2FA);

        if ($payout->balance->isAccountTypeDirect() === true &&
            $payout->balance->getChannel() === Balance\Channel::ICICI &&
            $isIcici2faEnabled === true)
        {
            $input += [Adapter\Constants::WORKFLOW_TYPE => Adapter\Constants::ICICI_PAYOUT_APPROVAL_TYPE];
        }
        else
        {
            $input += [Adapter\Constants::WORKFLOW_TYPE => Adapter\Constants::PAYOUT_APPROVAL_TYPE];
        }

        $res = (new WorkflowServiceClient)->createWorkflow($payout, $input);

        // Create Workflow Entity Mapping
        (new EntityMap\Core)->create($res, $payout);

        // Mark and save payout as pending
        $this->handleEarlyWorkflowResponse($payout);

        return $payout;
    }

    /**
     * Check if workflow is enabled for merchant
     * Additionally check if the call is from API and merchant has disabled the workflow for API request
     *
     * @param $skipWorkflow
     * @param bool $enableWorkflowForInternalContact
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function isWorkflowApplicable($skipWorkflow, bool $enableWorkflowForInternalContact = false)
    {
        //
        // Skip workflow for internally created payouts
        //
        if (($this->isInternal === true)and
            ($enableWorkflowForInternalContact === false))
        {
            $this->workflowFeature = Payout\WorkflowFeature::SKIP_FOR_INTERNAL_PAYOUT;

            return false;
        }

        //
        // Skip workflow if:
        // test mode
        //
        if ($this->isTestMode() === true)
        {
            return false;
        }

        $isPayoutFromPGBalance = ($this->balance->getType() === Balance\Type::PRIMARY);

        //
        // Skip workflow if:
        // Payout is from pg
        //
        if ($isPayoutFromPGBalance === true)
        {
            $this->workflowFeature = Payout\WorkflowFeature::SKIP_FOR_PG_PAYOUT;

            return false;
        }

        $areWorkflowsEnabled = $this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS);

        //
        // Skip workflow if:
        // workflow is not enabled for the merchant
        //
        if ($areWorkflowsEnabled === false)
        {
            return false;
        }
        else
        {
            $this->workflowFeature = Features::PAYOUT_WORKFLOWS;
        }

        // if App is Payout Link(and skip_workflow is present), respect it
        // else continue with the flow i.e. Payout Approval should be applicable as per the existing flow
        // (respecting all the existing payout-features)
        // New Flow:
        // 1. Payout Link gets created and goes through approval
        // 2. After approval, the link is sent to customer
        // 3. Customer enters his find account details and a payout gets created
        // 4. for this new payout, we don't want workflow to kick again
        // (as payout link has already gone through approval) so skip_workflow = true for such payouts
        if ((new Payout\Service())->isPayoutLinkApp() === true)
        {
            if (($skipWorkflow !== null) and ($skipWorkflow === true))
            {
                $this->workflowFeature = Features::SKIP_WF_FOR_PAYOUT_LINK;

                return false;
            }
            else
            {
                return true;
            }
        }

        if ($skipWorkflow !== null)
        {
            $hasSkipWorkflowPayoutSpecificFeature = $this->merchant->isFeatureEnabled(Features::SKIP_WF_AT_PAYOUTS);

            if ($hasSkipWorkflowPayoutSpecificFeature === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Skip workflow is not  enabled.",
                    'skip_workflow');
            }

            if ($skipWorkflow === true)
            {
                $this->workflowFeature = Features::SKIP_WF_AT_PAYOUTS;

                return false;
            }
        }

        $hasSkipWorkflowFeature = $this->merchant->isFeatureEnabled(Features::SKIP_WORKFLOWS_FOR_API);

        $isApiRequest = $this->app['basicauth']->isStrictPrivateAuth();

        //
        // Skip workflow if:
        // if the workflow is enabled, if the request if from API and merchant wants to
        // skip workflow for requests through API
        //
        if (($isApiRequest === true) and
            ($hasSkipWorkflowFeature === true))
        {
            $this->workflowFeature = Features::SKIP_WORKFLOWS_FOR_API;

            return false;
        }

        $hasSkipWorkflowForPayroll = $this->merchant->isFeatureEnabled(Features::SKIP_WF_FOR_PAYROLL);

        if(($hasSkipWorkflowForPayroll === true) and
           ((new Payout\Service())->isXPayrollApp() === true))
        {
            $this->workflowFeature = Features::SKIP_WF_FOR_PAYROLL;

            return false;
        }

        return true;
    }

    /**
     * Set the customer relation for the Payout.
     * To be used only for the customer wallet use case: customer_id is treated
     * as a Payout source
     *
     * @param Customer\Entity $customer
     *
     * @return self
     */
    public function setSourceCustomer(Customer\Entity $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    protected function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $fundAccountId = $input[Payout\Entity::FUND_ACCOUNT_ID];

        if ($this->fundAccount === null)
        {
            /** @var FundAccount\Entity $fundAccount */
            $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);
        }
        else
        {
            $fundAccount = $this->fundAccount;
        }

        if ($fundAccount->isActive() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payouts cannot be created on an inactive fund account',
                Payout\Entity::FUND_ACCOUNT_ID,
                [
                    Payout\Entity::FUND_ACCOUNT_ID => $fundAccountId,
                    Payout\Entity::CONTACT_ID      =>  "cont_" . $fundAccount->getSourceId(),
                ]
            );
        }

        if (optional($fundAccount->source)->isActive() === false)
        {
            $sourceEntity = $fundAccount->source->getEntity();

            throw new Exception\BadRequestValidationFailureException(
                'Payouts cannot be created on an inactive ' . $sourceEntity . ' fund account',
                Payout\Entity::FUND_ACCOUNT_ID,
                [
                    Payout\Entity::FUND_ACCOUNT_ID => $fundAccountId,
                    Payout\Entity::CONTACT_ID      =>  "cont_" . $fundAccount->getSourceId(),
                ]
            );
        }

        if ($this->merchant->isFeatureEnabled(Features::HANDLE_VA_TO_VA_PAYOUT))
        {
            $this->handleVaToVaPayouts($payout, $fundAccount);
        }
        else
        {
            $this->blockVaToVaPayouts($payout, $fundAccount, $input);
        }

        $this->checkAndBlockPayoutIfMerchantHasBlacklistedVpa($input, $fundAccount);

        $this->validateFundAccountContact($fundAccount);

        $payout->fundAccount()->associate($fundAccount);

        $this->fundTransferDestination = $fundAccount->account;
    }

    public function validateFundAccountContact(FundAccount\Entity $fundAccount)
    {
        return;
    }

    /**
     * Create Payout will drive the payout cycle for merchant/customer.
     *
     * @param array $input
     *
     * @return Payout\Entity
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function createPayoutEntity(array $input)
    {
        $payout = (new Payout\Entity);

        $queuePayoutCreateRequest = array_pull($input, Payout\Entity::QUEUE_PAYOUT_CREATE_REQUEST, false);

        $feeType = array_pull($input, Payout\Entity::FEE_TYPE, null);

        $sourceDetails = $this->pullSourceDetails($input);

        $payout->setInputSourceDetails($sourceDetails);

        $this->runInputValidations($payout, $input);

        $this->processPayoutLinkId($payout, $input);

        $payout->merchant()->associate($this->merchant);

        $payout->customer()->associate($this->customer);

        $this->fetchAndAssociatePayoutAccount($payout, $input);

        $this->setMethod($payout);

        // overriding mode to "IFT" in case of VA to VA payouts using creditTransfers
        if ($payout->getIsCreditTransferBasedPayout() === true)
        {
            $input[Entity::MODE] = Payout\Mode::IFT;
        }

        $payout->balance()->associate($this->balance);
        //
        // Doing this after all the associations since
        // the modifiers and validators require payout
        // account and merchant to be associated.
        //
        $payout = $payout->build($input);

        $this->validateSubAccountPayoutAndSetPayoutType($payout);

        //
        // Doing only user and batch association after build because
        // since it is present in $defaults, the association
        // gets overridden with the default value (null)
        // in the build function.
        // NOTE: Not sure why it does not happen with FundAccount. (todo: check)
        //
        $this->associateUserIfApplicable($payout);

        $payout->setWorkflowFeature($this->workflowFeature);

        $this->batchId ? ($payout->setBatchId($this->batchId)) : ($payout->batch()->associate($this->batch));

        $this->runEntityValidations($payout, $input);

        $payout->setQueuePayoutCreateRequest($queuePayoutCreateRequest);

        $payout->setExpectedFeeType($feeType);

        if (($this->isPayoutDetailsApplicable($input) === true) or
            ($payout->isSubAccountPayout() === true))
        {
            $this->setPayoutQueueFlag($input, $payout);

            $payoutDetailsInput = $this->preparePayoutDetailsFromRequestInput($input, $payout);

            (new PayoutsDetailsCore)->create($payoutDetailsInput, $payout);
        }

        (new Payout\Purpose)->setPurposeAndTypeForPayout($payout, $payout->getPurpose(), $this->isInternal);

        $this->checkMerchantEligibilityForPayoutMode($payout);

        if (isset($input[Payout\Entity::SCHEDULED_AT]) === true)
        {
            (new Payout\Schedule)->updateScheduleTimeToStartOfHour($payout);
        }

        return $payout;
    }

    public function validateSubAccountPayoutAndSetPayoutType(Entity $payout)
    {
        if ($this->balance->isAccountTypeShared() === false)
        {
            return;
        }

        if ($this->merchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT) === false)
        {
            return;
        }

        /** @var SubVirtualAccount\Entity $subVirtualAccount */
        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountFromSubAccountNumber($this->balance->getAccountNumber(), true);

        if ((empty($subVirtualAccount) === true) or
            ($subVirtualAccount->getSubAccountType() !== SubVirtualAccount\Type::SUB_DIRECT_ACCOUNT))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST);
        }

        $masterBASD = $this->repo->banking_account_statement_details->fetchAccountStatementByBalance($subVirtualAccount->getMasterBalanceId());

        if (($masterBASD === null) or
            (in_array($masterBASD->getStatus(), BASD\Status::getStatusesForWhichSubAccountPayoutIsAllowed()) === false))
        {
            throw new BadRequestValidationFailureException(
                "Payouts not supported for the debit account."
            );
        }

        $payout->setType(Entity::SUB_ACCOUNT);

        $payout->setMasterBalance($subVirtualAccount->balance);

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_PAYOUT_TYPE_SET);

        $this->trace->count(Metric::SUB_ACCOUNT_PAYOUT_TYPE_SET_TOTAL,
                            [
                                SubVirtualAccount\Entity::MASTER_BALANCE_ID => $subVirtualAccount->balance->getId(),
                                SubVirtualAccount\Entity::SUB_MERCHANT_ID   => $subVirtualAccount->getSubMerchantId(),
                            ]);
    }

    protected function createPayoutEntityForNewCompositePayoutFlow(array $input, bool $compositePayoutSaveOrFail = true, array $metadata = [])
    {
        $payout = (new Payout\Entity);

        $queuePayoutCreateRequest = array_pull($input, Payout\Entity::QUEUE_PAYOUT_CREATE_REQUEST, false);

        $this->runInputValidations($payout, $input);

        $payout->merchant()->associate($this->merchant);

        $this->fetchAndAssociatePayoutAccount($payout, $input);

        $this->setMethod($payout);

        $payout->balance()->associate($this->balance);

        //
        // Doing this after all the associations since
        // the modifiers and validators require payout
        // account and merchant to be associated.
        //
        $payout = $payout->build($input);

        if ($compositePayoutSaveOrFail === false)
        {
            $payout->setCreatedAt(Carbon::now(Timezone::IST)->getTimestamp());
        }

        if (empty($metadata) === false)
        {
            if (array_key_exists(Entity::ID, $metadata) === true)
            {
                $payout->setId($metadata[Entity::ID]);
            }

            if (array_key_exists(Entity::CREATED_AT, $metadata) === true)
            {
                $payout->setCreatedAt($metadata[Entity::CREATED_AT]);
            }
        }

        $this->runEntityValidations($payout, $input);

        $payout->setQueuePayoutCreateRequest($queuePayoutCreateRequest);

        if ($this->isPayoutDetailsApplicable($input) === true)
        {
            if ($compositePayoutSaveOrFail === true)
            {
                $this->setPayoutQueueFlag($input, $payout);

                $payoutDetailsInput = $this->preparePayoutDetailsFromRequestInput($input);

                (new PayoutsDetailsCore)->create($payoutDetailsInput, $payout);
            }
        }

        (new Payout\Purpose)->setPurposeAndTypeForNewCompositePayoutFlow($payout, $payout->getPurpose());

        $this->checkMerchantEligibilityForPayoutMode($payout);

        return $payout;
    }

    protected function isPayoutDetailsApplicable($input)
    {
        $isQueuePayoutParamApplicable = ((isset($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true) and
                    (boolval($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true));

        $isTdsPresent = (isset($input[PayoutsDetailsEntity::TDS]) === true);

        $isAttachmentPresent = (isset($input[PayoutsDetailsEntity::ATTACHMENTS]) === true);

        $isSubTotalAmountPresent = (isset($input[PayoutsDetailsEntity::SUBTOTAL_AMOUNT]) === true);

        return ($isQueuePayoutParamApplicable or $isTdsPresent or $isAttachmentPresent or $isSubTotalAmountPresent);
    }

    protected function setPayoutQueueFlag(array $input, Payout\Entity $payout)
    {
        if (isset($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true)
        {
            $queueIfLowBalanceFlag = boolval($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]);

            if ($queueIfLowBalanceFlag === true)
            {
                $payout->setQueueFlag(true);
            }
        }
    }

    protected function preparePayoutDetailsFromRequestInput(array $input, Entity $payout = null)
    {
        $payoutDetailsInput = array();

        if (isset($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true)
        {
            $queueIfLowBalanceFlag = boolval($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]);

            if ($queueIfLowBalanceFlag === true)
            {
                $payoutDetailsInput[PayoutsDetailsEntity::QUEUE_IF_LOW_BALANCE_FLAG] = $queueIfLowBalanceFlag;
            }
        }

        $additionalInfo = array();

        if (isset($input[PayoutsDetailsEntity::TDS]) === true)
        {
            $tdsDetails = $input[PayoutsDetailsEntity::TDS];

            $payoutDetailsInput[PayoutsDetailsEntity::TDS_CATEGORY_ID] = $tdsDetails[PayoutsDetailsEntity::CATEGORY_ID];

            $additionalInfo[PayoutsDetailsEntity::TDS_AMOUNT_KEY] = (float) $tdsDetails[PayoutsDetailsEntity::TDS_AMOUNT];
        }

        if (isset($input[PayoutsDetailsEntity::SUBTOTAL_AMOUNT]) === true)
        {
            $additionalInfo[PayoutsDetailsEntity::SUBTOTAL_AMOUNT_KEY] = (float) $input[PayoutsDetailsEntity::SUBTOTAL_AMOUNT];
        }

        if (isset($input[PayoutsDetailsEntity::ATTACHMENTS]) === true)
        {
            $attachmentsInfo = PayoutsDetailsUtils::prepareAttachmentInfoFromInput($input);

            $additionalInfo[PayoutsDetailsEntity::ATTACHMENTS_KEY] = $attachmentsInfo;
        }

        if (($payout !== null) and ($payout->getPayoutType() === Payout\Entity::SUB_ACCOUNT))
        {
            $additionalInfo[PayoutsDetailsEntity::MASTER_BALANCE_ID] = $payout->getMasterBalance()->getId();
            $additionalInfo[PayoutsDetailsEntity::MASTER_MERCHANT_ID] = $payout->getMasterBalance()->getMerchantId();
        }

        if (empty($additionalInfo) === false)
        {
            $payoutDetailsInput[PayoutsDetailsEntity::ADDITIONAL_INFO] = json_encode($additionalInfo, true);
        }

        return $payoutDetailsInput;
    }

    protected function processPayoutLinkId(Payout\Entity & $payout, array & $input)
    {
        $payoutLinkId = array_pull($input , Payout\Entity::PAYOUT_LINK_ID);

        if (empty($payoutLinkId) === false)
        {
            //if payout link service down , do not allow to create payout
            $this->checkIfPLServiceIsDown();

            // check if payout link microservice feature flag enabled for this merchant
            if($this->checkIfMerchantOnAPI() == true)
            {
                $payoutLink = $this->repo->payout_link->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

                $payout->payoutLink()->associate($payoutLink);
            }
            else
            {
                $payout->setPayoutLinkId($payoutLinkId);
            }
        }
    }

    protected function preValidations()
    {
        $payoutFromCA = false;

        if ($this->balance->isAccountTypeDirect() === true and $this->balance->isTypeBanking() === true
                        and (new Merchant\Core())->isCurrentAccountActivated($this->merchant)=== true)
        {
            $payoutFromCA = true;
        }

        // If Payout is from Current Account, we don't check funds_on_hold
        // If SKIP_HOLD_FUNDS_ON_PAYOUT feature is enabled for merchant,
        // then we don't check the merchant funds_on_hold and proceed with payout creation
        // We also want to skip funds_on_hold if the payout is internally generated by Razorpay
        //
        // We check funds_on_hold only for live mode. We don't care about funds on hold in test mode.
        //
        if (($this->isLiveMode() === true) and
            ($this->merchant->getHoldFunds() === true) and
            ($this->merchant->isFeatureEnabled(Features::SKIP_HOLD_FUNDS_ON_PAYOUT) === false) and
            ($this->isInternal === false) and ($payoutFromCA === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }

        $this->blockBankingVaPayoutsIfApplicable();
    }

    /*
     * This function checks if the merchant has the feature block_va_payouts enabled and blocks payout
     * creation. Currently it is being used to block payouts initiated from shared banking balance ONLY.
     */
    protected function blockBankingVaPayoutsIfApplicable()
    {
        if (($this->balance->isTypeBanking() === false) or
            ($this->balance->isAccountTypeShared() === false))
        {
            return;
        }

        if ($this->merchant->isFeatureEnabled(Feature::BLOCK_VA_PAYOUTS) === true)
        {
            throw new BadRequestValidationFailureException("Payouts not supported for the debit account");
        }
    }

    protected function runInputValidations(Payout\Entity $payout, array $input)
    {
        $validatorOperation = camel_case($this->getPayoutType());

        $validator = $payout->getValidator();

        $validator->validateInput($validatorOperation, $input);
    }

    protected function getPayoutType()
    {
        return class_basename(get_called_class());
    }

    protected function setPayoutBalance(array $input, Balance\Entity $balance = null)
    {
        if (empty($balance) === false)
        {
            $this->balance = $balance;

            return;
        }

        $balanceId = $input[Payout\Entity::BALANCE_ID] ?? null;

        if (empty($balanceId) === true)
        {
            $this->balance = $this->merchant->primaryBalance;
        }
        else
        {
            $this->balance = $this->repo->balance->findByPublicIdAndMerchant($balanceId, $this->merchant);
        }
    }

    protected function fireEventForPayoutStatus(Payout\Entity $payout)
    {
        return;
    }

    /**
     * Naive audit logging.
     * Sets the user for requests from dashboard (proxy_auth)
     * On private auth, user_id is unset.
     *
     * @param Payout\Entity $payout
     */
    protected function associateUserIfApplicable(Payout\Entity $payout)
    {
        $user = app('basicauth')->getUser();

        $payout->user()->associate($user);
    }

    protected function setMethod(Payout\Entity $payout)
    {
        $destinationType = $this->fundTransferDestination->getEntity();

        $method = Payout\Method::$destinationMethodMap[$destinationType];

        $payout->setMethod($method);
    }

    protected function runEntityValidations(Payout\Entity $payout, array $input)
    {
        return;
    }

    protected function verifyPayoutAmountRuleBeforeProcessing(Payout\Entity $payout, PayoutAmountRules\Entity $payoutAmountRuleBeforeWorkflow)
    {
        $amount = $payout->getAmount();

        $payoutAmountRule = (new PayoutAmountRules\Core)->fetchPayoutAmountRuleForMerchantIfDefined($amount, $this->merchant);

        /*
         * This check is to see, that the payout amount rule is present
         * and not deleted by any workflow edit operation
         */
        if (empty($payoutAmountRule) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_RULE_NOT_FOUND,
                null,
                ['payout' => $payout->toArrayPublic()]);
        }

        /*
         * This check is to see, that the same payout amount rule is
         * valid, before and after workflow trigger. This will be a strict
         * check to ensure the payout amount rule has not been changed by
         * workflow edit
         */
        if ($payoutAmountRuleBeforeWorkflow->getId() !== $payoutAmountRule->getId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_RULE_CHANGED,
                null,
                ['payout' => $payout->toArrayPublic()]);
        }

        $mutexResource = $payoutAmountRule['id'] . self::KEY_SUFFIX;

        /*
         * This check is to see, that whether any workflow edit
         * is currently operating on the payout amount rule
         */
        if (empty($mutexResource) === false)
        {
            $redis = $this->app['redis']->connection('mutex_redis');

            $mutexResource = Mutex::PREFIX . $mutexResource;

            $resourceRedisValue = $redis->get($mutexResource);

            if (empty($resourceRedisValue) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_EDIT_IN_PROGRESS,
                    null,
                    ['payout' => $payout->toArrayPublic()]
                );
            }
        }
    }

    /**
     * @param Payout\Entity $payout
     *
     * @throws BadRequestException
     */
    protected function schedulePayoutIfApplicable(Payout\Entity & $payout)
    {
        if ($payout->toBeScheduled() === false)
        {
            return;
        }

        Payout\Schedule::validateScheduledAtTimeStamp($payout->getScheduledAt());

        $payout->setStatus(Status::SCHEDULED);

        $this->repo->saveOrFail($payout);
    }

    /**
     * @param Payout\Entity $payout
     * @param PayoutAmountRules\Entity|null $payoutAmountRuleBeforeWorkflow
     * @throws BadRequestException
     */
    protected function handleEarlyWorkflowResponse(
        Payout\Entity $payout,
        PayoutAmountRules\Entity $payoutAmountRuleBeforeWorkflow = null): void
    {
        if ($payout === null)
        {
            $this->trace->critical(TraceCode::PAYOUT_WORKFLOW_ACTION_EXCEPTION);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE,
                null,
                [
                    'payout'                 => $payout,
                    'payout_amount_rules'    => $payoutAmountRuleBeforeWorkflow,
                ]);
        }

        // Set payout to pending and move on
        $payout->setStatus(Payout\Status::PENDING);

        if ($payoutAmountRuleBeforeWorkflow !== null)
        {
            $this->trace->info(TraceCode::PAYOUT_WORKFLOW_TRIGGERED, ['payout' => $payout->toArray()]);

            $this->verifyPayoutAmountRuleBeforeProcessing($payout, $payoutAmountRuleBeforeWorkflow);
        }

        $this->repo->saveOrFail($payout);

        (new PayoutsStatusDetailsCore())->create($payout);

        $this->app->events->dispatch('api.payout.pending', [$payout]);

        $this->workflowActivated = true;
    }

    // We don't want to save payout entity in API first and then at Payout
    // MS.
    protected function handleEarlyWorkflowResponseForPayoutService(
        Payout\Entity $payout,
        PayoutAmountRules\Entity $payoutAmountRuleBeforeWorkflow = null): array
    {
        if ($payoutAmountRuleBeforeWorkflow !== null)
        {
            $this->trace->info(TraceCode::PAYOUT_WORKFLOW_TRIGGERED, ['payout' => $payout->toArray()]);

            $this->verifyPayoutAmountRuleBeforeProcessing($payout, $payoutAmountRuleBeforeWorkflow);
        }

        return [
            Entity::IS_WORKFLOW_ACTIVATED  => true,
            Entity::ERROR                  => null,
        ];
    }


    protected function checkIfPLServiceIsDown()
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return;

        $mid = $this->merchant->getId();

        $variant = $this->app['razorx']->getTreatment($mid,
                                                      Merchant\RazorxTreatment::RX_IS_PAYOUT_LINK_SERVICE_DOWN,
                                                      $this->app['rzp.mode'] ?? 'live');

        if ($variant == 'on')
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_SERVICE_UNDER_MAINTAINENCE,
                null,
                [
                    'merchant_id'    => $this->merchant->getId()
                ]
            );
        }
    }

    protected function checkIfMerchantOnAPI() : bool
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return false;

        $mid = $this->merchant->getId();

        $variant = $this->app['razorx']->getTreatment($mid,
                                                      Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE,
                                                      $this->app['rzp.mode'] ?? 'live');

        return !($variant == 'on');
    }

    protected function incrementCounterAndSetExpectedFeeTypeForFundAccountPayouts(Payout\Entity $payout)
    {
        if (snake_case($this->getPayoutType()) === 'fund_account_payout')
        {
            $feeType = $this->repo->counter->transaction(
                function() use ($payout)
                {
                    /** @var Balance\Entity $balance */
                    $balance = $this->repo->balance->findOrFailById($payout->getBalanceId());

                    return (new CounterHelper)->updateFreePayoutConsumedIfApplicable($balance);
                });

            $payout->setExpectedFeeType($feeType);
        }

        return $payout;
    }

    protected function dispatchForPreCreatedPayouts(Entity $payout)
    {
        try
        {
            $hashValue = 1;
            // TODO: Add the hash logic back comment once we have setup more queues on prod.
            // $hashValue = $this->djbHash($payout->getId());

            $highTpsMerchantFlag = $payout->merchant->isFeatureEnabled(Constants::HIGH_TPS_COMPOSITE_PAYOUT);

            $highTpsIngressFlag = $payout->merchant->isFeatureEnabled(Constants::HIGH_TPS_PAYOUT_INGRESS);

            $asyncIngressFlag = $payout->merchant->isFeatureEnabled(Constants::PAYOUT_ASYNC_INGRESS);

            if ($highTpsIngressFlag === true or $highTpsMerchantFlag === true)
            {
                // Manually setting this to 0 so that the payout goes via the low priority queue
                // and not via the normal queue
                $hashValue = 0;
            }

            if ($hashValue === 0)
            {
                if ($asyncIngressFlag === false)
                {
                    PayoutPostCreateProcessLowPriority::dispatch($this->mode, $payout->getId(), $payout->toBeQueued());

                    $this->trace->info(
                        TraceCode::PAYOUT_CREATE_SUBMITTED_REQUEST_ENQUEUED_LOW_PRIORITY,
                        [
                            'payout_id' => $payout->getId(),
                        ]);
                }
            }
            else
            {
                PayoutPostCreateProcess::dispatch($this->mode, $payout->getId(), $payout->toBeQueued());

                $this->trace->info(
                    TraceCode::PAYOUT_CREATE_SUBMITTED_REQUEST_ENQUEUED,
                    [
                        'payout_id' => $payout->getId(),
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_PAYOUT_CREATE_REQUEST
            );

            $alertData = [
                'payout_id' => $payout->getId(),
            ];

            (new SlackNotification)->send(
                    'Failed to enqueue payout create request',
                    $alertData,
                    $e,
                    1,
                    'x-payouts-core-alerts');

            throw $e;
        }
    }

    protected function djbHash($str)
    {
        for ($i = 0, $h = 5381, $len = strlen($str); $i < $len; $i++)
        {
            $h = (($h << 5) + $h + ord($str[$i])) & 0x7FFFFFFF;
        }

        return $h%2;
    }

    protected function pullSourceDetails(array & $input)
    {
        $sourceDetails = null;

        if (isset($input[Entity::SOURCE_DETAILS]) === true)
        {
            $sourceDetails = array_pull($input, Entity::SOURCE_DETAILS);
        }

        return $sourceDetails;
    }

    protected function processSourceDetails(array $sourceDetails, Entity $payout)
    {
        foreach ($sourceDetails as $sourceDetail)
        {
            (new PayoutSource\Core)->create($sourceDetail, $payout);
        }
    }

    protected function isPayoutInitiatedByPartner(Entity $payout)
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        $applicationId = $this->app['basicauth']->getOAuthApplicationId();

        if (empty($partnerMerchantId) === false and
            empty($applicationId) === false)
        {
            (new PayoutMetaCore())->create($partnerMerchantId, $applicationId, $payout);
        }
    }

    protected function handleVaToVaPayouts(Payout\Entity $payout, FundAccount\Entity $fundAccount)
    {
        // We are making sure that the source account is Banking VA
        if ($this->balance->getAccountType() === Balance\AccountType::SHARED)
        {
            $fundAccountType = $fundAccount->getAccountType();

            switch ($fundAccountType)
            {
                case FundAccount\Type::BANK_ACCOUNT:
                    $this->handleVaToVaPayoutForFundAccountWithBankAccountType($payout, $fundAccount);
                    break ;

                case FundAccount\Type::VPA:
                    $this->handleVaToVaPayoutForFundAccountWithVpaType($payout, $fundAccount);
            }
        }
    }

    protected function handleVaToVaPayoutForFundAccountWithBankAccountType(Payout\Entity $payout, FundAccount\Entity $fundAccount)
    {
        // if the destination account is a virtual account of type bank account
        if ($fundAccount->isAccountVirtualBankAccount() === true)
        {
            $sourceAccountNumber = $this->balance->getAccountNumber();

            $sourceUnderlyingAccountType = $this->getUnderlyingAccountTypeForBankingVA($sourceAccountNumber);

            $destinationAccountNumber = $fundAccount->account->getAccountNumber();

            $destinationUnderlyingAccountType = $this->getUnderlyingAccountTypeForBankingVA($destinationAccountNumber);

            // We should not allow internal transfers between current account VA and nodal account VA
            // We block payouts from banking VA to Non banking VA
            if ($sourceUnderlyingAccountType !== $destinationUnderlyingAccountType)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED,
                    null,
                    [
                        'merchant_id'                         => $payout->getMerchantId(),
                        'fund_account_id'                     => $fundAccount->getId(),
                        'source_underlying_account_type'      => $sourceUnderlyingAccountType,
                        'destination_underlying_account_type' => $destinationUnderlyingAccountType
                    ]);
            }

            // check if destination merchant is enabled for VA to VA transfers
            if ($this->checkIfDestinationVaIsActiveAndMerchantWhitelisted($payout, $fundAccount) === true)
            {
                $payout->setIsCreditTransferBasedPayout(true);

                return;
            }

            // check if source merchant is enabled for VA to VA transfers
            if ($this->checkIfSourceMerchantEnabledForVaToVaPayouts($payout, $fundAccount) === true)
            {
                $payout->setIsCreditTransferBasedPayout(true);

                return;
            }

            //if both source and destination merchants are not enabled
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                null,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);
        }
    }

    protected function handleVaToVaPayoutForFundAccountWithVpaType(Payout\Entity $payout, FundAccount\Entity $fundAccount)
    {
        $vpa = $fundAccount->account;

        $doesVpaBelongsToVa = $this->repo->vpa->checkIfVpaBelongsToVirtualAccount($vpa);

        if($doesVpaBelongsToVa === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                null,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);
        }
    }

    protected function getUnderlyingAccountTypeForBankingVA(string $accountNumber)
    {
        $firstFourDigitsOfAccountNumber = substr($accountNumber, 0, 4);

        $firstSixDigitsOfAccountNumber = substr($accountNumber, 0, 6);

        if (array_key_exists($firstFourDigitsOfAccountNumber,
            FundAccount\Entity::PREFIX_TO_UNDERLYING_ACCOUNT_TYPE_MAP))
        {
            return FundAccount\Entity::PREFIX_TO_UNDERLYING_ACCOUNT_TYPE_MAP[$firstFourDigitsOfAccountNumber] ;
        }

        if (array_key_exists($firstSixDigitsOfAccountNumber,
            FundAccount\Entity::PREFIX_TO_UNDERLYING_ACCOUNT_TYPE_MAP))
        {
            return FundAccount\Entity::PREFIX_TO_UNDERLYING_ACCOUNT_TYPE_MAP[$firstSixDigitsOfAccountNumber] ;
        }

        return Balance\Type::PRIMARY;
    }

    protected function checkIfDestinationVaIsActiveAndMerchantWhitelisted(Payout\Entity $payout, FundAccount\Entity $fundAccount): bool
    {
        $bankAccount = $fundAccount->account;

        // We are maintaining a list of destination bank accounts on redis that we shall allow
        // the merchant to create payouts to.
        $destinationMIDsToWhitelist = (new AdminService)->getConfigKey(
            [
                'key' => ConfigKey::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS
            ]);

        $destinationVirtualAccount = $this->repo->virtual_account
                                                ->getActiveVirtualAccountFromAccountNumberAndIfsc(
                                                    $bankAccount->getAccountNumber(),
                                                    $bankAccount->getIfscCode()
                                                );

        // if there is no active VA, we throw an error
        if ($destinationVirtualAccount === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NO_ACTIVE_BENEFICIARY_VA_FOUND,
                null,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);
        }

        $destinationBalanceId = $destinationVirtualAccount->getBalanceId();

        if ($destinationBalanceId === $this->balance->getId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUT_ON_SAME_ACCOUNT,
                null,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);
        }

        $destinationMerchantId = $destinationVirtualAccount->getMerchantId();

        if (in_array($destinationMerchantId, $destinationMIDsToWhitelist, true) === true)
        {
            $this->trace->info(TraceCode::PAYOUT_VA_TO_VA_ALLOWED_BASED_ON_DESTINATION,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);

            return true;
        }

        return false;
    }

    protected function checkIfSourceMerchantEnabledForVaToVaPayouts(Payout\Entity $payout, FundAccount\Entity $fundAccount)
    {
        $variant = $this->app['razorx']->getTreatment(
            $payout->getMerchantId(),
            Merchant\RazorxTreatment::RX_ALLOW_VA_TO_VA_PAYOUTS,
            $this->mode,
            3);

        $isVaToVaPayoutsAllowed = $this->merchant->isFeatureEnabled(Features::ALLOW_VA_TO_VA_PAYOUTS);

        // variant will be control when:
        // 1. Merchant is not part of the `on` variant, meaning merchant is not allowed VA to VA payouts
        // 2. If RazorX request fails
        if (($variant === 'on') or
            ($isVaToVaPayoutsAllowed == true))
        {
            $this->trace->info(TraceCode::PAYOUT_VA_TO_VA_ALLOWED_BASED_ON_SOURCE,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'fund_account_id'   => $fundAccount->getId()
                ]);

            return true;
        }

        return false;
    }

    protected function blockVaToVaPayouts(Payout\Entity $payout, FundAccount\Entity $fundAccount, array $input)
    {
        $blockVAToVAPayouts = false;

        // We are adding another check here. This is to block VA to VA payouts
        // Step 1: We are making sure that the source account is VA
        if ($this->balance->getAccountType() === Balance\AccountType::SHARED)
        {
            // Step 2: We make sure that the destination FA is type bank account
            if ($fundAccount->getAccountType() === FundAccount\Type::BANK_ACCOUNT)
            {
                $bankAccount = $fundAccount->account;

                $ifscCode = $bankAccount->getIfscCode();

                $firstFourDigitsOfAccountNumber = substr($bankAccount->getAccountNumber(), 0, 4);

                // We have a mapping of account number prefixes and IFSCs. We use the prefixes as keys and the IFSCs
                // as values. If the mapping exists, and the IFSC of the fund account matches with a VA's IFSC,
                // we block the payout

                if ((FundAccount\Entity::PREFIX_TO_IFSC_MAPPING_FOR_VIRTUAL_ACCOUNTS[$firstFourDigitsOfAccountNumber] ?? '')
                                                                                            === $ifscCode)
                {
                    $blockVAToVAPayouts = true;
                }

                $firstSixDigitsOfAccountNumber = substr($bankAccount->getAccountNumber(), 0, 6);

                if ((FundAccount\Entity::PREFIX_TO_IFSC_MAPPING_FOR_VIRTUAL_ACCOUNTS[$firstSixDigitsOfAccountNumber] ?? '')
                                                                                            === $ifscCode)
                {
                    $blockVAToVAPayouts = true;
                }

                if ($blockVAToVAPayouts === true)
                {
                    // We are maintaining a list of destination bank accounts on redis that we shall allow
                    // the merchant to create payouts to.
                    $destinationMIDsToWhitelist = (new AdminService)->getConfigKey(
                        [
                            'key' => ConfigKey::RX_VA_TO_VA_PAYOUTS_WHITELISTED_DESTINATION_MERCHANTS
                        ]);

                    $doesDestinationAccountBelongToWhitelistedMerchants
                        = $this->repo
                               ->bank_account
                               ->checkIfBankAccountBelongsToWhitelistedMerchant($bankAccount,
                                                                                $destinationMIDsToWhitelist);

                    if ($doesDestinationAccountBelongToWhitelistedMerchants === true)
                    {
                        $blockVAToVAPayouts = false;

                        $this->trace->info(TraceCode::PAYOUT_VA_TO_VA_ALLOWED_BASED_ON_DESTINATION,
                            [
                                'merchant_id'       => $payout->getMerchantId(),
                                'fund_account_id'   => $fundAccount->getId()
                            ]);
                    }
                }
            }

            if($fundAccount->getAccountType() === FundAccount\Type::VPA)
            {
                $vpa = $fundAccount->account;

                $internalVpaIfExists = $this->repo->vpa->checkIfVpaBelongsToVirtualAccount($vpa);

                if(is_null($internalVpaIfExists) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                        null,
                        [
                            'merchant_id'       => $payout->getMerchantId(),
                            'fund_account_id'   => $fundAccount->getId()
                        ]);
                }
            }
        }

        // Below mentioned variable will only be true when destination accountNumber is of type VA and the
        // first 4 characters of the IFSC match with a corresponding VA IFSC.
        // We shall now check if the merchant has been allowed VA to VA payouts via the experiment
        if ($blockVAToVAPayouts === true)
        {
            $variant = $this->app['razorx']->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::RX_ALLOW_VA_TO_VA_PAYOUTS,
                $this->mode,
                3);

            $isVaToVaPayoutsAllowed = $this->merchant->isFeatureEnabled(Features::ALLOW_VA_TO_VA_PAYOUTS);

            // This will be control when:
            // 1. Merchant is not part of the `on` variant, meaning merchant is not allowed VA to VA payouts
            // 2. If RazorX request fails
            // 3. And merchant does not have the feature to allow va to va.
            if (($variant === 'control') and
                ($isVaToVaPayoutsAllowed == false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED,
                    null,
                    [
                        'merchant_id'       => $payout->getMerchantId(),
                        'fund_account_id'   => $fundAccount->getId()
                    ]);
            }
            else
            {
                $this->trace->info(TraceCode::PAYOUT_VA_TO_VA_ALLOWED,
                    [
                        'merchant_id'       => $payout->getMerchantId(),
                        'fund_account_id'   => $fundAccount->getId()
                    ]);
            }
        }
    }

    protected function checkMerchantEligibilityForM2PPayout(Payout\Entity $payout)
    {
        $purposeType = $payout->getPurposeType();
        $cardNetwork = $payout->fundAccount->account->getNetwork();

        $isMerchantBlacklistedForM2P = (new FundTransfer\Mode)->m2pMerchantBlacklisted($this->merchant,
                                                                                        $cardNetwork,
                                                                                        $purposeType);

        if ($isMerchantBlacklistedForM2P[FundTransfer\Mode::BLACKLISTED] === true)
        {
            $merchantId = $this->merchant->getId();

            $this->trace->error(
                TraceCode::MERCHANT_BLOCKED_FOR_CARD_MODE,
                [
                    Payout\Entity::MERCHANT_ID => $merchantId,
                    self::ERROR_CODE  => $isMerchantBlacklistedForM2P[FundTransfer\Mode::ERROR_CODE]
                ]
            );

            throw new Exception\BadRequestException(
                $isMerchantBlacklistedForM2P[FundTransfer\Mode::ERROR_CODE],
                null,
                [
                    Payout\Entity::MERCHANT_ID => $merchantId
                ]);
        }

        (new FundTransferMetric)->pushM2PTransfersCount();
    }

    protected function checkMerchantEligibilityForAmazonPayPayout()
    {
        if ((new WalletAccount\Service)->isWalletAccountAmazonPayFeatureDisabled() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_AMAZONPAY_PAYOUTS_NOT_PERMITTED,
                null,
                [
                    FundAccount\Entity::MERCHANT_ID => $this->merchant->getId(),
                ]);
        }
    }

    protected function checkMerchantEligibilityForPayoutMode(Payout\Entity $payout)
    {
        $payoutMode = $payout->getMode();

        switch ($payoutMode)
        {
            case FundTransfer\Mode::CARD:
                $this->checkMerchantEligibilityForM2PPayout($payout);
                break;

            case FundTransfer\Mode::AMAZONPAY:
                $this->checkMerchantEligibilityForAmazonPayPayout();
                break;

            default:
                break;
        }
    }

    protected function getInputForPayoutEntity(array $params)
    {
        $input = [
            Payout\Entity::FEE_TYPE        => $params[Payout\Entity::FEE_TYPE] ?? null,
            Payout\Entity::AMOUNT          => $params[Payout\Entity::AMOUNT] ?? null,
            Payout\Entity::PURPOSE         => $params[Payout\Entity::PURPOSE] ?? null,
            Payout\Entity::CURRENCY        => $params[Payout\Entity::CURRENCY] ?? null,
            Payout\Entity::BALANCE_ID      => $params[Payout\Entity::BALANCE_ID] ?? null,
            Payout\Entity::FUND_ACCOUNT_ID => $params[Payout\Entity::FUND_ACCOUNT_ID] ?? null,
            Payout\Entity::MODE            => $params[Payout\Entity::MODE] ?? null,
            Payout\Entity::REFERENCE_ID    => $params[Payout\Entity::REFERENCE_ID] ?? null,
            Payout\Entity::NARRATION       => $params[Payout\Entity::NARRATION] ?? null,
            Payout\Entity::NOTES           => $params[Payout\Entity::NOTES] ?? [],
            Payout\Entity::ORIGIN          => $params[Payout\Entity::ORIGIN] ?? Entity::API,
        ];

        if (empty($params[Payout\Entity::SOURCE_DETAILS]) === false)
        {
            $input[Payout\Entity::SOURCE_DETAILS] = $params[Payout\Entity::SOURCE_DETAILS];
        }

        if (empty($params[Payout\Entity::SCHEDULED_AT]) === false)
        {
            $input[Payout\Entity::SCHEDULED_AT] = $params[Payout\Entity::SCHEDULED_AT];
        }

        return $input;
    }
    /**
     * Function is called from payout Microservice to create payout in api.
     *
     * @param array $params
     * @return array
     */
    public function createPayoutEntry(array $params): array
    {
        try
        {
            $this->trace->info(TraceCode::PAYOUT_CREATE_REQUEST_FROM_MICROSERVICE,
                [
                    'input' => $params
                ]);

            $id = $params[Payout\Entity::ID];

            $payout = $this->repo->payout->find($id);

            if (is_null($payout) === true)
            {
                $this->setPayoutBalance($params);

                $input = $this->getInputForPayoutEntity($params);

                $status = $params[Payout\Entity::STATUS];

                $workflowDetails = $params['workflow_details'];

                $payout = $this->repo->transaction(function () use ($input, $id, $status, $workflowDetails)
                {
                    $this->workflowFeature = !empty($workflowDetails['workflow_feature']) ? $workflowDetails['workflow_feature'] : null;

                    $payout = $this->createPayoutEntity($input);

                    if ($id !== null)
                    {
                        $payout->setId($id);

                        $payout->setIsPayoutService(1);

                        $payout->setSavePayoutServicePayoutFlag(true);
                    }

                    if (empty($payout->getStatus()) === true)
                    {
                        $payout->setStatus($status);
                    }

                    // Save payout Entity to database
                    $this->repo->saveOrFail($payout);

                    if($status === Status::PENDING)
                    {
                        (new PayoutsStatusDetailsCore())->create($payout);
                    }

                    $sourceDetails = $payout->getInputSourceDetails();

                    if (empty($sourceDetails) === false)
                    {
                        $this->processSourceDetails($sourceDetails, $payout);
                    }

                    $this->createWorkflowEntityMapEntry($workflowDetails, $payout);

                    $this->trace->info(TraceCode::PAYOUT_CREATED_FOR_MICROSERVICE,
                        [
                            'input' => $input,
                            'payout' => $payout->toArray()
                        ]);

                    return $payout;
                });
            }

            $response =  [
                Entity::STATUS => $payout->getStatus(),
                Entity::ERROR  => $payout->getFailureReason(),
            ];
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_CREATE_FAILED_FOR_MICROSERVICE,
                [
                    'input' => $params,
                ]
            );

            $response = [
                Entity::STATUS           => null,
                Entity::ERROR            => $exception->getMessage(),
                Error::PUBLIC_ERROR_CODE => $exception->getCode()
            ];
        }

        $this->trace->info(TraceCode::PAYOUT_RESPONSE_FOR_MICROSERVICE,
            [
                'response' => $response
            ]);

        return $response;
    }

    public function createWorkflowEntityMapEntry(array $workflowDetails, Payout\Entity $payout) {

        $workflowServiceEnabled = $workflowDetails['workflow_service_enabled'];

        if ($workflowServiceEnabled == true) {
            $input = [
                EntityMap\Entity::WORKFLOW_ID => $workflowDetails[Entity::ID],
                EntityMap\Entity::CONFIG_ID => $workflowDetails[EntityMap\Entity::CONFIG_ID]
            ];

            (new EntityMap\Core)->create($input, $payout);
        }
    }

    public function createWorkflowPayoutEntry(array $params)
    {
        $this->trace->info(TraceCode::WORKFLOW_FOR_PAYOUT_CREATE_REQUEST_FROM_MICROSERVICE,
            [
                'input' => $params,
            ]);

        $this->setPayoutBalance($params);

        $input = $this->getInputForPayoutEntity($params);

        try
        {
            $payout = $this->createPayoutEntity($input);

            $payoutAmountRuleBeforeWorkflow = (new PayoutAmountRules\Core)->fetchPayoutAmountRuleForMerchantIfDefined(
                                                                            $params[Entity::AMOUNT],
                                                                            $this->merchant);


            $payout->setId($params[Entity::ID]);
            //
            // Initiate the workflow process. If a workflow is triggered successfully,
            // this function will thrown an EarlyWorkflowResponse exception.
            //
            // This method also checks if a given payout already has a workflow action
            // created against it
            $this->app['workflow']
                ->setEntityAndId(Entity::PAYOUT, $params[Entity::ID])
                ->setPermission(Permission\Name::CREATE_PAYOUT)
                ->handle((new \stdClass), $payout);

            $response = [
                Entity::ERROR                    => null,
                Entity::IS_WORKFLOW_ACTIVATED    => false,
            ];
        }
        catch (Exception\EarlyWorkflowResponse $ex)
        {
            // If flow reaches this catch block then workflow got activated.
           $response = $this->handleEarlyWorkflowResponseForPayoutService($payout, $payoutAmountRuleBeforeWorkflow);
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException(
                $t,
                Trace::ERROR,
                TraceCode::WORKFLOW_FOR_PAYOUT_CREATE_FAILED_FOR_MICROSERVICE,
                [
                    'input' => $input,
                ]);

            if ($t->getCode() == ErrorCode::BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS)
            {
                $response = [
                    Entity::ERROR                    => null,
                    Entity::IS_WORKFLOW_ACTIVATED    => true,
                ];
            }
            else
            {
                $response = [
                    Entity::ERROR                    => $t->getMessage(),
                    Entity::IS_WORKFLOW_ACTIVATED    => false,
                ];
            }
        }

        $this->trace->info(TraceCode::WORKFLOW_FOR_PAYOUT_CREATE_RESPONSE_FROM_MICROSERVICE,
            [
                'response' => $response
            ]);

        return $response;
    }

    /**
     * Function is called from payout Microservice to create Fta in api.
     *
     * @param string $payoutId
     * @return array
     */
    public function createFTAForPayoutService(Entity $payout): array
    {
        try
        {
           return $this->mutex->acquireAndRelease(
               self::FTA_CREATION_PAYOUT_SERVICE . $payout->getId(),
               function() use ($payout)
               {
                   // Enable fts sync flow. Pushing/Pulling via queue adds to the latency.
                   $payout->setSyncFtsFundTransferFlag(true);

                   $payoutId = $payout->getId();

                   $this->trace->info(TraceCode::FTA_CREATE_REQUEST_FROM_MICROSERVICE,
                       [
                           'payout_id' => $payoutId
                       ]);

                   $payout->reload();

                   $accountType = optional($payout->balance)->getAccountType();

                   $fta = $this->repo->fund_transfer_attempt->getAttemptBySourceId($payoutId, Entity::PAYOUT);

                   $status = $payout->getStatus();

                   if ((in_array($status, [Status::CREATED, Status::INITIATED], true) === true) and
                       (is_null($fta) === true) and
                       ((is_null($payout->getTransactionId()) === false) or
                        ($accountType === Balance\AccountType::DIRECT)))
                   {
                       $payoutType = $this->getPayoutType();

                       $this->fundTransferDestination = $payout->fundAccount->account;

                       $downstreamProcessor = new DownstreamProcessor($payoutType,
                           $payout,
                           $this->mode,
                           $this->fundTransferDestination);

                       $downstreamProcessor->processCreateFundTransferAttempt();

                       // Make sync call to FTS.
                       $this->syncFTSFundTransfer($payout);
                   }
                   else if (is_null($payout->getTransactionId()) === true)
                   {
                       return [
                           Entity::STATUS => $status,
                           Entity::ERROR => "Ledger entry not found in payout."
                       ];
                   }

                   return [
                       Entity::STATUS => $status,
                       Entity::ERROR => null
                   ];
               },
               $this->payoutServiceMutexTTL,
               ErrorCode::BAD_REQUEST_FTA_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FTA_CREATION_FAILED_FOR_MICROSERVICE,
                [
                    'payout_id' => $payout->getId(),
                ]
            );

            return [
                Entity::STATUS         => null,
                Entity::ERROR          => $exception->getMessage(),
            ];
        }
    }

    /**
     * Function is called from payout Microservice to create transaction(ledger) in api.
     *
     * @param array $params
     * @return array
     */
    public function createPayoutServiceTransaction(array $params): array
    {
        $payoutId = $params[Payout\Entity::ID];
        $this->trace->info(TraceCode::TRANSACTION_CREATE_REQUEST_FROM_MICROSERVICE,
            [
                'payout_id' => $payoutId,
                'params' => $params
            ]);

        return $this->mutex->acquireAndRelease(
            self::LEDGER_CREATION_PAYOUT_SERVICE . $payoutId,
            function() use ($payoutId, $params)
            {
                $payout = null;

                try
                {
                    /** @var Entity $payout */
                    $payout = (new Payout\Core)->getAPIModelPayoutFromPayoutService($payoutId);

                    $queueFlag = $params[Payout\Entity::QUEUE_IF_LOW_BALANCE] ?? false;

                    $this->trace->info(TraceCode::TRANSACTION_CREATE_REQUEST_FROM_MICROSERVICE,
                        [
                            'payout_id' => $payoutId
                        ]);

                    $status = $payout->getStatus();

                    if (($status === Status::CREATE_REQUEST_SUBMITTED) or
                        ($status === Status::SCHEDULED) or
                        ($status === Status::QUEUED) or
                        ($status === Status::PENDING) or
                        ($status === Status::ON_HOLD))
                    {
                        $payout = $this->processPayoutPostCreate($payout, $queueFlag);

                        $this->trace->info(TraceCode::TRANSACTION_CREATED_FROM_MICROSERVICE,
                            [
                                Entity::TRANSACTION_ID => $payout->getTransactionId(),
                                'payout_id'            => $payoutId
                            ]);
                    }

                    $response = [
                        Entity::FEES            => $payout->getFees(),
                        Entity::TAX             => $payout->getTax(),
                        Entity::TRANSACTION_ID  => $payout->getTransactionId(),
                        Entity::STATUS          => $payout->getStatus(),
                        Entity::ERROR           => $payout->getFailureReason(),
                        Entity::FEE_TYPE        => $payout->getFeeType(),
                        Entity::PRICING_RULE_ID => $payout->getPricingRuleId(),
                        Entity::QUEUED_REASON   => $payout->getQueuedReason(),
                        Entity::QUEUED_AT       => $payout->getQueuedAt(),
                        Entity::STATUS_CODE     => $payout->getStatusCode()
                    ];

                    $this->trace->info(TraceCode::TRANSACTION_RESPONSE_FOR_MICROSERVICE,
                        [
                            'response' => $response
                        ]);

                    return $response;

                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::TRANSACTION_CREATION_FAILED_FOR_MICROSERVICE,
                        [
                            'payout_id' => $payoutId,
                        ]
                    );

                    //if (is_null($payout) === false)
                    //{
                    //    $payout->setStatus(Status::FAILED);
                    //
                    //    $payout->setFailureReason('Payout failed. Contact support for help');
                    //
                    //    $this->repo->saveOrFail($payout);
                    //}

                    return [
                        Entity::STATUS         => null,
                        Entity::ERROR          => $exception->getMessage(),
                    ];
                }
            },
            $this->payoutServiceMutexTTL,
            ErrorCode::BAD_REQUEST_LEDGER_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS);
    }

    /**
     * Function is called from payout Microservice to deduct credits in api.
     * This is needed because credits are owned by api for now.
     * Once credits are migrated to payout service then this won't be needed.
     *
     * @param array $params
     * @return array
     */
    public function deductCreditsViaPayoutService(array $params): array
    {
        $this->trace->info(TraceCode::PAYOUT_SERVICE_DEDUCT_CREDITS_REQUEST,
            [
                'params' => $params
            ]);

        try
        {
            $payoutId = $params[Payout\Entity::PAYOUT_ID];

            $fees = $params[Payout\Entity::FEES];

            $tax = $params[Payout\Entity::TAX];

            /*
             * Check if credits already deducted or not
             */
            $creditsAlreadyDeducted = (new Credits\Transaction\Core)->checkIfCreditsDeductedForSource($payoutId, RzpConstants\Entity::PAYOUT);

            if ($creditsAlreadyDeducted === true)
            {

                $this->trace->info(TraceCode::PAYOUT_SERVICE_DEDUCT_CREDITS_DUPLICATE_REQUEST,
                    [
                        'payout_id' => $payoutId
                    ]);

                return [
                    Entity::FEES            => $fees - $tax,
                    Entity::TAX             => 0,
                    'credits_used'          => true,
                ];
            }

            $payout = (new Payout\Entity);

            $payout->setId($payoutId);

            $payout->setFees($fees);

            $payout->setTax($tax);

            /*
                Setting some default value here since this is needed to identify downstream processor.
            */
            $payout->setChannel(BankingAccount\Channel::YESBANK);

            $merchant = (new Merchant\Repository)->findOrFail($params[Payout\Entity::MERCHANT_ID]);

            $payout->merchant()->associate($merchant);

            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findOrFailById($params[Entity::BALANCE_ID]);

            $payout->balance()->associate($balance);

            $downstreamProcessor = new DownstreamProcessor($this->getPayoutType(), $payout, $this->mode);

            $downstreamProcessor->processAdjustFeeAndTaxesIfCreditsAvailable();

            $response = [
                Entity::FEES            => $payout->getFees(),
                Entity::TAX             => $payout->getTax(),
            ];

            if ($payout->getFeeType() === CreditType::REWARD_FEE)
            {
                $response += [
                    'credits_used' => true,
                ];
            }
            else
            {
                $response += [
                    'credits_used' => false,
                ];
            }

            $this->trace->info(TraceCode::PAYOUT_SERVICE_DEDUCT_CREDITS_RESPONSE,
                [
                    'response' => $response
                ]);

            return $response;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_DEDUCT_CREDITS_REQUEST_FAILED,
                [
                    'payout_id' => $payoutId,
                ]
            );

            throw new Exception\ServerErrorException(
                'Internal error occurred while deducting merchant credits via payout service.',
                ErrorCode::SERVER_ERROR,
                [
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Function is called from payout Microservice to fetch pricing info in api.
     *
     * @param array $params
     * @return array
     */
    public function fetchPricingInfoForPayoutService(array $params): array
    {
        try
        {
            $payoutId = $params[Payout\Entity::PAYOUT_ID];

            $payout = (new Payout\Entity);

            $payout->setId($payoutId);

            $payout->setIsPayoutService(1);

            $payout->setMethod($params[Entity::METHOD]);

            $payout->setAmount($params[Entity::AMOUNT]);

            $payout->setMode($params[Entity::MODE]);

            $payout->setChannel($params[Entity::CHANNEL]);

            if (isset($params[Entity::PURPOSE]) === true)
            {
                $payout->setPurpose($params[Entity::PURPOSE]);
            }

            if (isset($params[Entity::FEE_TYPE]) === true)
            {
                $payout->setFeeType($params[Entity::FEE_TYPE]);
            }

            if (isset($params[Entity::USER_ID]) === true)
            {
                $payout->setUserId($params[Entity::USER_ID]);
            }

            $merchantId = $params[Payout\Entity::MERCHANT_ID];

            $merchant = (new Merchant\Repository)->findOrFail($merchantId);

            $payout->merchant()->associate($merchant);

            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findOrFailById($params[Entity::BALANCE_ID]);

            $payout->balance()->associate($balance);

            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType, $payout, $this->mode);

            $downstreamProcessor->processFetchPricingInfoForPayoutsService();

            $response = [
                Entity::FEES            => $payout->getFees(),
                Entity::TAX             => $payout->getTax(),
                Entity::PRICING_RULE_ID => $payout->getPricingRuleId(),
            ];

            $this->trace->info(TraceCode::PAYOUT_SERVICE_FETCH_PRICING_INFO_RESPONSE,
                               [
                                   'response' => $response
                               ]);

            return $response;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FETCH_PRICING_INFO_FOR_MICROSERVICE_FAILED,
                [
                    'payout_id' => $payoutId,
                ]
            );

            return [
                Entity::ERROR            => $exception->getMessage(),
                Error::PUBLIC_ERROR_CODE => strval($exception->getCode()),
            ];
        }
    }

    /**
     * Check if we need to call payout microservice for payout creation
     *
     * @return bool
     */
    protected function isPayoutServiceIfApplicable(array $input) : bool
    {
        if (($this->mode == Mode::LIVE) and
            ($this->balance->getAccountType() === AccountType::SHARED))
        {
            $this->isPayoutServiceEnabled = $this->merchant->isFeatureEnabled(Features::PAYOUT_SERVICE_ENABLED);

            if ($this->isPayoutServiceEnabled === true)
            {
                if ((isset($input[Payout\Entity::BATCH_ID]) === true) or
                    (isset($input[Payout\Entity::IDEMPOTENCY_KEY]) === true) or
                    (empty($this->batchId) === false))
                {
                    $this->trace->error(
                        TraceCode::INVALID_PAYOUT_CREATE_REQUEST_TO_PAYOUT_SERVICE,
                        [
                            'merchant_id'         => $this->merchant->getMerchantId(),
                            'payout_create_input' => $input,
                            'batch_id'            => $this->batchId ?? "",
                        ]);

                    /** @var Route $route */
                    $route = $this->app['api.route'];

                    $routeName = $route->getCurrentRouteName();

                    $this->trace->count(Metric::INVALID_PAYOUT_CREATE_REQUEST_TO_PAYOUT_SERVICE, [
                        RzpConstants\Metric::LABEL_ROUTE_NAME  => $routeName,
                    ]);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        null,
                        'batch_id, idempotency_key is/are not required and should not be sent');
                }

                $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

                $applicationId = $this->app['basicauth']->getOAuthApplicationId();

                if (empty($partnerMerchantId) === false and
                    empty($applicationId) === false)
                {
                    $this->trace->info(TraceCode::PAYOUT_SERVICE_NOT_APPLICABLE_FOR_PS_ENABLED_MERCHANT,
                                       [
                                           'partner_merchant_id' => $partnerMerchantId,
                                           'application_id'      => $applicationId
                                       ]);

                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Check if we need to call payout microservice for payout creation
     * i.e not card or direct payout.
     * If payout created by service. Then payout will be created in api too
     * with transaction and FTS call already been made.
     * We will fetch payout via ID from payout service response.
     *
     * else return null
     *
     * @param array $input
     * @return mixed|null
     */
    protected function createPayoutViaMicroservice(array $input)
    {
        if ($this->isPayoutServiceIfApplicable($input) === true)
        {
            $input[Balance\Entity::ACCOUNT_NUMBER] = $this->balance->getAccountNumber();

            if ($this->balance->getAccountType() === AccountType::SHARED)
            {
                [$fetchUnusedCreditsSuccess, $unusedCredits] =  (new Credits\Transaction\Core)->fetchMerchantUnusedCredits(
                                                                           $this->merchant,
                                                                           CreditType::REWARD_FEE,
                                                                           Product::BANKING);

                [$fetchFundAccountInfoSuccess, $fundAccountInfo] = (new FundAccount\Core)->fetchFundAccountForPayoutServiceProcessing($this->merchant->getId(), $input);

                $response = $this->payoutCreateServiceClient->createPayoutViaMicroservice($input,
                                                                                          $this->merchant->getId(),
                                                                                          $this->isInternal,
                                                                                          [
                                                                                              Payout\Entity::FETCH_UNUSED_CREDITS_SUCCESS => $fetchUnusedCreditsSuccess,
                                                                                              Payout\Entity::UNUSED_CREDITS => $unusedCredits
                                                                                          ],
                                                                                          [
                                                                                              Payout\Entity::FETCH_FUND_ACCOUNT_INFO_SUCCESS => $fetchFundAccountInfoSuccess,
                                                                                              Payout\Entity::FUND_ACCOUNT => $fundAccountInfo
                                                                                          ]);

                $this->trace->info(
                    TraceCode::PAYOUT_CREATE_RESPONSE_FROM_MICROSERVICE,
                    [
                        'response' => $response
                    ]);

                $id = $response[Entity::ID];
                $id = Entity::verifyIdAndStripSign($id);

                $payout = new Payout\Entity;

                $payout->setId($id);
                $payout->setIsPayoutService(1);

                $payout->payoutServiceResponse = $response;

                return $payout;
            }
        }

        return null;
    }

    /**
     * Check if the payout needs to be blocked if the merchant has a blacklisted VPA configured
     *
     * @throws BadRequestException if the payout needs to be blocked
     */
    protected function checkAndBlockPayoutIfMerchantHasBlacklistedVpa(array $input, FundAccount\Entity $fundAccount)
    {
        $payoutMode = $input[Entity::MODE] ?? null;

        if ($payoutMode !== PayoutMode::UPI)
        {
            return null;
        }

        if ($fundAccount->getAccountType() !== FundAccount\Type::VPA)
        {
            return null;
        }

        $vpa = $fundAccount->account;

        $vpaId = $vpa->getAddress();

        $merchantId = $this->merchant->getMerchantId();

        try
        {
            $blacklistedVpaRegexesForMerchants = (new AdminService)->getConfigKey(
                [
                    'key' => ConfigKey::RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS
                ]);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::ERROR_FETCHING_BLACKLISTED_VPA_REGEXES_FROM_REDIS,
                [
                    'merchant_id' => $merchantId,
                    'config_key'  => ConfigKey::RX_BLACKLISTED_VPA_REGEXES_FOR_MERCHANT_PAYOUTS,
                ]);

            // Fail-safe here and continue processing
            return null;
        }

        // Common regexes that should be applied to all merchants
        $commonBlacklistedVpaRegexes = $blacklistedVpaRegexesForMerchants['FOR_ALL_MERCHANTS'] ?? array();

        // Regexes configured to apply only for this merchant
        $merchantBlacklistedVpaRegexesToApply = $blacklistedVpaRegexesForMerchants[$merchantId]['apply'] ?? array();

        // Regexes configured to skip only for this merchant
        $merchantBlacklistedVpaRegexesToSkip = $blacklistedVpaRegexesForMerchants[$merchantId]['skip'] ?? array();

        $blacklistedVpaRegexesToEvaluate = array_filter(
            array_merge($commonBlacklistedVpaRegexes, $merchantBlacklistedVpaRegexesToApply));

        // Exclude those regexes that are configured to skip
        $blacklistedVpaRegexesToEvaluate = array_diff(
            $blacklistedVpaRegexesToEvaluate, $merchantBlacklistedVpaRegexesToSkip);

        if (empty($blacklistedVpaRegexesToEvaluate) === true)
        {
            return null;
        }

        foreach ($blacklistedVpaRegexesToEvaluate as $blacklistedVpaRegex)
        {
            try
            {
                $shouldBlock = $this->checkIfVpaIdMatchesRegexToBlock($vpaId, $blacklistedVpaRegex);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::ERROR_CHECKING_VPA_AGAINST_REGEX,
                    [
                        'merchant_id' => $this->merchant->getMerchantId(),
                        'vpa_regex'   => $blacklistedVpaRegex,
                        'vpa_id'      => $vpaId,
                    ]);

                // Fail-safe here and continue as this could be due to a misconfigured regex
                continue;
            }

            if ($shouldBlock === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_TO_VPA_IS_BLOCKED,
                    [
                        'merchant_id'           => $this->merchant->getMerchantId(),
                        'vpa_id'                => $vpaId,
                        'blacklisted_vpa_regex' => $blacklistedVpaRegex,
                        'fund_account_id'       => $fundAccount->getPublicId(),
                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    'Payouts to this VPA ID are not allowed');
            }
        }

        return null;
    }

    protected function checkIfVpaIdMatchesRegexToBlock(string $vpaId, string $vpaRegex): bool
    {
        $isMatched = preg_match($vpaRegex, strtolower($vpaId));

        if ($isMatched === 1)
        {
            return true;
        }

        return false;
    }

    protected function saveUserCommentInSettingsEntity(Entity $payout, string $userComment)
    {
        $accessor = Settings\Accessor::for($payout, Settings\Module::PAYOUTS);

        $accessor->upsert(Payout\Core::USER_COMMENT_KEY_IN_SETTINGS_FOR_ICICI_2FA, $userComment);

        $accessor->save();
    }
}
