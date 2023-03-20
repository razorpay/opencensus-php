<?php


namespace RZP\Models\CreditTransfer;

use Monolog\Logger;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\SubVirtualAccount as SubVA;
use RZP\Jobs\QueuedCreditTransferRequests;
use RZP\Models\Transaction\Core as TxnCore;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Transaction\Processor\CreditTransfer as CreditTransferTxnProcessor;

class Core extends Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;

    const CREDIT_TRANSFER_MUTEX_LOCK_TIMEOUT = 180;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function createCreditTransfer(array $creditTransferRequest, bool $isQueued = false)
    {
        (new Validator)->validateInput('create_input', $creditTransferRequest);

        $this->mutex->acquireAndRelease(
            'ct_' . $creditTransferRequest[Constants::SOURCE_ENTITY_ID] . '_' . $creditTransferRequest[Constants::SOURCE_ENTITY_TYPE],
            function() use ($creditTransferRequest, $isQueued)
            {
                try
                {
                    $creditTransfer = $this->create($creditTransferRequest);

                    $this->process($creditTransfer);

                    if (($creditTransfer->isStatusProcessed() === true) or
                        ($creditTransfer->isStatusFailed() === true))
                    {
                        $this->notifyPostProcessingOfCreditTransfer($creditTransfer);
                    }
                }
                catch (\Throwable $throwable)
                {
                    $traceInfo = [
                        "credit_transfer_request" => $creditTransferRequest
                    ];

                    $this->trace->traceException($throwable, null, TraceCode::CREDIT_TRANSFER_PROCESSING_FAILED, $traceInfo);

                    if ($isQueued === false)
                    {
                        $this->trace->info(TraceCode::CREDIT_TRANSFER_REQUEST_QUEUE_DISPATCH_INITIATE, $traceInfo);

                        QueuedCreditTransferRequests::dispatch($this->mode, $creditTransferRequest);

                        $this->trace->info(TraceCode::CREDIT_TRANSFER_REQUEST_QUEUE_DISPATCH_COMPLETE, $traceInfo);
                    }
                }
            },
            self::CREDIT_TRANSFER_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_CREDIT_TRANSFER_ALREADY_BEING_PROCESSED);
    }

    public function createAsync(array $input)
    {
        (new Validator)->validateInput('create_input', $input);

        $traceInfo = [
            "credit_transfer_request" => $input
        ];

        $this->trace->info(TraceCode::CREDIT_TRANSFER_REQUEST_QUEUE_DISPATCH_INITIATE, $traceInfo);

        QueuedCreditTransferRequests::dispatch($this->mode, $input);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_REQUEST_QUEUE_DISPATCH_COMPLETE, $traceInfo);

        return [
            "status" => Constants::ACCEPTED
        ];
    }

    protected function create(array $input): Entity
    {
        $creditTransfer = $this->repo->credit_transfer->findCreditTransferBySourceId($input[Constants::SOURCE_ENTITY_ID]);

        if (is_null($creditTransfer) === false)
        {
            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_ALREADY_EXISTS,
                [
                    'source_id'              => $input[Constants::SOURCE_ENTITY_ID],
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]
            );

            return $creditTransfer;
        }

        $this->trace->info(
            TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_REQUEST,
            [
                'input'                       => $input,
            ]
        );

        $payeeAccountType = $input[Entity::PAYEE_ACCOUNT_TYPE];

        AccountType::validate($payeeAccountType);

        $payeeDetails = $input[Constants::PAYEE_DETAILS];

        $payeeVirtualAccount = null;

        $payeeAccountId = null;

        switch ($payeeAccountType)
        {
            case AccountType::BANK_ACCOUNT:
                $payeeVirtualAccount = $this->repo->virtual_account
                                            ->getActiveVirtualAccountFromAccountNumberAndIfsc(
                                                $payeeDetails[BankAccountEntity::ACCOUNT_NUMBER],
                                                $payeeDetails[BankAccountEntity::IFSC_CODE]
                                            );

                // if there is no active VA, we throw an error
                if ($payeeVirtualAccount === null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_NO_ACTIVE_VIRTUAL_ACCOUNT_FOUND,
                        null,
                        [
                            Constants::PAYEE_DETAILS      => $payeeDetails,
                            Entity::PAYEE_ACCOUNT_TYPE    => $payeeAccountType,
                            Constants::SOURCE_ENTITY_ID   => $input[Constants::SOURCE_ENTITY_ID],
                            Constants::SOURCE_ENTITY_TYPE => $input[Constants::SOURCE_ENTITY_TYPE],
                        ]);
                }

                $payeeAccountId = $payeeVirtualAccount->getBankAccountId();

                break ;
        }

        $balance = $payeeVirtualAccount->balance;

        $creditTransferInput = $this->buildCreditTransferInputFromInput($input);

        $creditTransferInput = array_merge($creditTransferInput, [
            Entity::PAYEE_ACCOUNT_ID   => $payeeAccountId,
            Entity::PAYEE_ACCOUNT_TYPE => $payeeAccountType
        ]);

        $creditTransfer = $this->buildCreditTransferEntityAndAssociations($creditTransferInput, $balance);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_SUCCESS,
            [
                'credit_transfer' => $creditTransfer->toArrayPublic()
            ]
        );

        return $creditTransfer;
    }

    protected function process(Entity $creditTransfer)
    {
        if (($creditTransfer->isStatusProcessed() === true) or
            ($creditTransfer->isStatusFailed() === true))
        {
            return ;
        }

        if ($this->shouldCreditTransferGoThroughLedgerReverseShadowFlow($creditTransfer) === true)
        {
            try
            {
                $this->processCreditTransferThroughLedger($creditTransfer);

                $creditTransfer->setStatus(Status::PROCESSED);

                $utr = UniqueIdEntity::generateUniqueId();

                $creditTransfer->setUtr($utr);

                $this->repo->saveOrFail($creditTransfer);

                $this->trace->info(
                    TraceCode::CREDIT_TRANSFER_PROCESS_SUCCESS,
                    $creditTransfer->toArrayPublic());
            }
            catch(\Throwable $ex)
            {
                // trace and ignore exception as it will be retries in async
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_FAILURE,
                    [
                        'credit_transfer_id' => $creditTransfer->getId(),
                    ]
                );
            }

            return null;
        }

        $creditTransfer = $this->repo->transaction(function () use ($creditTransfer)
        {
            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_PROCESS_ATTEMPT,
                $creditTransfer->toArrayPublic());

            $txn = (new Transaction\Core)->createFromCreditTransfer($creditTransfer);

            $this->repo->saveOrFail($txn);

            $creditTransfer->setStatus(Status::PROCESSED);

            $utr = UniqueIdEntity::generateUniqueId();

            $creditTransfer->setUtr($utr);

            $this->repo->saveOrFail($creditTransfer);

            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);

            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_PROCESS_SUCCESS,
                $creditTransfer->toArrayPublic());

            return $creditTransfer;
        });

        $this->processLedgerCreditTransfer($creditTransfer);

        return null;
    }

    public function moveCreditTransferToFailed(array $creditTransferRequest)
    {
        if (array_key_exists(Constants::SOURCE_ENTITY_ID, $creditTransferRequest) === false)
        {
            return;
        }

        $sourceEntityId = $creditTransferRequest[Constants::SOURCE_ENTITY_ID];

        $this->mutex->acquireAndRelease('ct_'. $sourceEntityId,
            function() use ($sourceEntityId)
            {
                $creditTransfer = $this->repo->credit_transfer->findCreditTransferBySourceId($sourceEntityId);

                if (is_null($creditTransfer) === false)
                {
                    $creditTransfer->getValidator()->validateCreditTransferForFailure();

                    $creditTransfer->setStatus(Status::FAILED);

                    $this->repo->saveOrFail($creditTransfer);

                    $this->notifyPostProcessingOfCreditTransfer($creditTransfer);
                }
            },
            self::CREDIT_TRANSFER_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_CREDIT_TRANSFER_ALREADY_BEING_PROCESSED);
    }

    protected function buildCreditTransferInputFromInput(array $input): array
    {
        $creditTransferInput = [
            Entity::AMOUNT             => $input[Entity::AMOUNT],
            Entity::CURRENCY           => $input[Entity::CURRENCY],
            Entity::CHANNEL            => $input[Entity::CHANNEL],
            Entity::MODE               => $input[Entity::MODE],
            Entity::ENTITY_ID          => $input[Constants::SOURCE_ENTITY_ID] ?? null,
            Entity::ENTITY_TYPE        => $input[Constants::SOURCE_ENTITY_TYPE] ?? null
        ];

        if (array_key_exists(Entity::DESCRIPTION, $input) === true)
        {
            $creditTransferInput[Entity::DESCRIPTION] = $input[Entity::DESCRIPTION];
        }

        if (array_key_exists(Entity::PAYER_ACCOUNT, $input) === true)
        {
            $creditTransferInput[Entity::PAYER_ACCOUNT] = $input[Entity::PAYER_ACCOUNT];
        }

        if (array_key_exists(Entity::PAYER_NAME, $input) === true)
        {
            $creditTransferInput[Entity::PAYER_NAME] = $input[Entity::PAYER_NAME];
        }

        if (array_key_exists(Entity::PAYER_IFSC, $input) === true)
        {
            $creditTransferInput[Entity::PAYER_IFSC] = $input[Entity::PAYER_IFSC];
        }

        if (array_key_exists(Entity::PAYER_MERCHANT_ID, $input) === true)
        {
            $creditTransferInput[Entity::PAYER_MERCHANT_ID] = $input[Entity::PAYER_MERCHANT_ID];
        }

        return $creditTransferInput;
    }

    public function notifyPostProcessingOfCreditTransfer(Entity $creditTransfer)
    {
        try
        {
            $entityName = $creditTransfer->getSourceEntityName();

            if ($entityName === null)
            {
                return;
            }

            $sourceCoreClass = EntityConstants::getEntityNamespace($entityName) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateEntityPostProcessingOfCreditTransfer') === true)
            {
                $sourceCore->updateEntityPostProcessingOfCreditTransfer($creditTransfer);
            }
        }
        catch (\Throwable $throwable)
        {
            $traceInfo = [
                Constants::SOURCE_ENTITY_ID   => $creditTransfer->getSourceEntityId(),
                Constants::SOURCE_ENTITY_TYPE => $creditTransfer->getEntityName(),
                "credit_transfer_id"          => $creditTransfer->getId(),
                "credit_transfer_status"      => $creditTransfer->getStatus()
            ];

            $this->trace->traceException($throwable, null, TraceCode::CREDIT_TRANSFER_SOURCE_ENTITY_NOTIFY_FAILURE, $traceInfo);
        }
    }

    public function processLedgerCreditTransfer(Entity $creditTransfer)
    {
        // In case env variable ledger.enabled is false, return.
        // We shall also skip the ledger creation
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($creditTransfer->balance->isTypeBanking() === false))
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($creditTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        $event = $this->getLedgerEventBasedOnCreditTransferShadowMode($creditTransfer);

        (new Ledger\CreditTransfer)->pushTransactionToLedger($creditTransfer, $event);
    }

    public function getLedgerEventBasedOnCreditTransferReverseShadowMode(Entity $creditTransfer)
    {
        if ($creditTransfer->isStatusCreated() === true)
        {
            return Ledger\CreditTransfer::VA_TO_VA_CREDIT_PROCESSED;
        }

        return Ledger\CreditTransfer::DEFAULT_EVENT;
    }

    public function getLedgerEventBasedOnCreditTransferShadowMode(Entity $creditTransfer)
    {
        if ($creditTransfer->isStatusProcessed() === true)
        {
            return Ledger\CreditTransfer::VA_TO_VA_CREDIT_PROCESSED;
        }

        return Ledger\CreditTransfer::DEFAULT_EVENT;
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $creditTransfer = $this->repo->credit_transfer->find($entityId);

        if (self::shouldCreditTransferGoThroughLedgerReverseShadowFlow($creditTransfer) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                ['merchant_id' => $creditTransfer->getMerchantId()]);
        }

        $txn = $this->mutex->acquireAndRelease('ct_' . $entityId,
            function() use ($creditTransfer, $ledgerResponse) {

                $creditTransfer->reload();

                return $this->repo->transaction(function() use ($ledgerResponse, $creditTransfer) {

                    $txn = $this->createTransactionForLedgerReverseShadow($creditTransfer, $ledgerResponse);

                    $creditTransfer->transaction()->associate($txn);

                    $this->repo->saveOrFail($creditTransfer);

                    return $txn;
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity' => $creditTransfer->getPublicId(),
            'txn'    => $txn->getPublicId(),
        ];
    }

    public static function shouldCreditTransferGoThroughLedgerReverseShadowFlow(Entity $creditTransfer)
    {
        if (($creditTransfer->merchant->isFeatureEnabled(FeatureConstants::LEDGER_REVERSE_SHADOW) === true) and
            ($creditTransfer->getBalanceType() === Merchant\Balance\Type::BANKING) and
            ($creditTransfer->getBalanceAccountType() === Merchant\Balance\AccountType::SHARED))
        {
            return true;
        }

        return false;
    }

    public function processCreditTransferThroughLedger(Entity $creditTransfer)
    {
        $this->trace->info(
            TraceCode::CREDIT_TRANSFER_PROCESSING_THROUGH_LEDGER_BEGINS,
            [
                'payout_id' => $creditTransfer->getPublicId(),
            ]
        );

        $event = $this->getLedgerEventBasedOnCreditTransferReverseShadowMode($creditTransfer);

        $ledgerResponse = (new Ledger\CreditTransfer())->processCreditTransferAndCreateJournalEntry($creditTransfer, $event);
    }

    public function createTransactionForLedgerReverseShadow($creditTransfer, $ledgerResponse)
    {
        $this->trace->info(
            TraceCode::TRANSACTION_CREATE_FOR_LEDGER_REVERSE_SHADOW_BEGINS,
            [
                'entity_id'   => $creditTransfer->getPublicId(),
                'entity_name' => 'credit_transfer'
            ]
        );

        $txnId = $ledgerResponse[Entity::ID];

        $newBalance = Ledger\CreditTransfer::getMerchantBalanceFromLedgerResponse($ledgerResponse);

        list($txn, $feeSplit) = (new CreditTransferTxnProcessor($creditTransfer))->createTransactionForLedger($txnId, $newBalance);

        // if fee split is null, it may mean that a txn is already created.
        if ($feeSplit !== null)
        {
            $this->repo->saveOrFail($txn);

            (new TxnCore)->saveFeeDetails($txn, $feeSplit);

            // A dispatch may have already happened
            // which means a dispatch is not needed if fee split is null
            (new TxnCore())->dispatchEventForTransactionCreated($txn);
        }

        $this->trace->info(
            TraceCode::TRANSACTION_FOR_LEDGER_REVERSE_SHADOW_CREATED,
            [
                'entity_id' => $creditTransfer->getPublicId(),
            ]
        );

        return $txn;
    }

    public function processCreditTransferAfterLedgerStatusCheck($creditTransfer, $ledgerResponse)
    {
        $creditTransfer->setStatus(Status::PROCESSED);

        $this->repo->saveOrFail($creditTransfer);

        $this->notifyPostProcessingOfCreditTransfer($creditTransfer);

    }

    public function createCreditTransferForSubAccount(array $creditTransferRequest, Merchant\Balance\Entity $balance)
    {
        $this->trace->info(TraceCode::CREDIT_TRANSFER_FOR_SUB_ACCOUNT_CREATE_REQUEST,
                           [
                               'input'          => $creditTransferRequest,
                               'sub_balance_id' => $balance->getId(),
                           ]);

        $creditTransfer = $this->createForSubAccount($creditTransferRequest, $balance);

        try
        {
            $this->process($creditTransfer);
        }
        catch (\Throwable $ex)
        {
            (new SubVA\Metric())->pushMetrics(SubVA\Metric::SUB_ACCOUNT_CREDIT_TRANSFER_PROCESSING_FAILURES_TOTAL,
                                             [
                                                 SubVA\Entity::SUB_MERCHANT_ID    => $balance->merchant->getId(),
                                                 SubVA\Entity::MASTER_MERCHANT_ID => $creditTransferRequest[Entity::PAYER_MERCHANT_ID] ?? null
                                             ]);

            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::CREDIT_TRANSFER_FOR_SUB_ACCOUNT_PROCESSING_FAILED
            );

            $this->moveCreditTransferForSubAccountToFailedState($creditTransfer);
        }

        return $creditTransfer;
    }

    protected function buildCreditTransferEntityAndAssociations($creditTransferInput, Merchant\Balance\Entity $balance)
    {
        $creditTransfer = (new Entity);

        $creditTransfer->balance()->associate($balance);

        $creditTransfer->merchant()->associate($balance->merchant);

        $creditTransfer->setStatus(Status::CREATED);

        $creditTransfer = $creditTransfer->build($creditTransferInput);

        $this->associateUserIfApplicable($creditTransfer);

        $this->repo->saveOrFail($creditTransfer);

        return $creditTransfer;
    }

    protected function createForSubAccount($creditTransferRequest, $balance)
    {
        $creditTransferInput = $this->buildCreditTransferInputFromInput($creditTransferRequest);

        $creditTransfer = $this->buildCreditTransferEntityAndAssociations($creditTransferInput, $balance);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_FOR_SUB_ACCOUNT_TRANSFER_SUCCESS,
                           [
                               'credit_transfer' => $creditTransfer->toArrayPublic()
                           ]);

        return $creditTransfer;
    }

    protected function moveCreditTransferForSubAccountToFailedState(Entity $creditTransfer)
    {
        $this->mutex->acquireAndRelease('ct_' . $creditTransfer->getId(), function() use ($creditTransfer)
            {
                $creditTransfer->getValidator()->validateCreditTransferForFailure();

                $creditTransfer->setStatus(Status::FAILED);

                $this->repo->saveOrFail($creditTransfer);

                $this->notifyPostProcessingOfCreditTransfer($creditTransfer);
            },
                                        self::CREDIT_TRANSFER_MUTEX_LOCK_TIMEOUT,
                                        ErrorCode::BAD_REQUEST_CREDIT_TRANSFER_ALREADY_BEING_PROCESSED);
    }

    protected function associateUserIfApplicable(Entity $creditTransfer)
    {
        if ($creditTransfer->getSourceEntityId() === null)
        {
            $payerUser = $this->app['basicauth']->getUser();

            $creditTransfer->payerUser()->associate($payerUser);
        }
    }
}
