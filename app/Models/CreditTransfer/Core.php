<?php


namespace RZP\Models\CreditTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Jobs\Transactions;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Transaction\Core as TxnCore;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\CreditTransfer\Helper as Helper;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Transaction\Processor\CreditTransfer as CreditTransferTxnProcessor;

class Core extends Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function getHelper(Base\Entity $source): Helper\Base
    {
        $type = $source->getEntityName();

        $helper = __NAMESPACE__ ;

        $helper .= '\\Helper\\' .studly_case($type);

        return new $helper($source);
    }

    public function create(Base\Entity $source): Entity
    {
        $creditTransfer = $this->repo->credit_transfer->findCreditTransferBySourceId($source->getId());

        if (is_null($creditTransfer) === false)
        {
            $this->trace->info(
                TraceCode::CREDIT_TRANSFER_ALREADY_EXISTS,
                [
                    'source_id'              => $source->getId(),
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]
            );

            return $creditTransfer;
        }

        $this->trace->info(
            TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_REQUEST,
            [
                'source_id'              => $source->getId(),
            ]
        );

        $sourceEntityHelper = $this->getHelper($source);

        $creditAccountType = $sourceEntityHelper->getCreditAccountTypeFromSourceEntity();

        AccountType::validate($creditAccountType);

        $destinationVirtualAccount = $sourceEntityHelper->getDestinationVirtualAccountFromSourceEntity();

        $merchant = $destinationVirtualAccount->merchant;

        $balance = $destinationVirtualAccount->balance;

        $creditTransferInput = $sourceEntityHelper->getCreditTransferInputFromSourceEntity();

        switch ($creditAccountType)
        {
            case AccountType::BANK_ACCOUNT:
                $creditTransferInput = array_merge($creditTransferInput, [
                                            Entity::PAYEE_ACCOUNT_ID   => $destinationVirtualAccount->getBankAccountId(),
                                            Entity::PAYEE_ACCOUNT_TYPE => FundAccount\Type::BANK_ACCOUNT
                                        ]);
                break ;
        }

        $creditTransfer = (new Entity);

        $creditTransfer->balance()->associate($balance);

        $creditTransfer->merchant()->associate($merchant);

        $creditTransfer->entity()->associate($source);

        $creditTransfer->setStatus(Status::CREATED);

        $creditTransfer = $creditTransfer->build($creditTransferInput);

        $this->repo->saveOrFail($creditTransfer);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_ENTITY_CREATE_SUCCESS,
            [
                'credit_transfer' => $creditTransfer->toArrayPublic()
            ]
        );

        return $creditTransfer;
    }

    public function process(Entity $creditTransfer): Entity
    {
        $creditTransfer->reload();

        if ($creditTransfer->isStatusProcessed() === true)
        {
            return $creditTransfer;
        }

        if ($creditTransfer->isStatusFailed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_CREDIT_TRANSFER_ALREADY_FAILED,
                null,
                [
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]);
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

            return $creditTransfer;
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

        return $creditTransfer;
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

    public static function shouldCreditTransferGoThroughLedgerReverseShadowFlow($creditTransfer)
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

        // If it is a success, dispatch to queue for transactions creation
        try
        {
            Transactions::dispatch($this->mode,
                $creditTransfer->getId(),
                EntityConstants::CREDIT_TRANSFER,
                $ledgerResponse);
        }
        catch (\Throwable $ex)
        {
            // TODO: Set an alert. Check how to handle this failure.
            // Will probably need to give a route to retry creation
            $this->trace->info(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED,
                [
                    'credit_transfer_id' => $creditTransfer->getId(),
                    'entity_name'        => EntityConstants::CREDIT_TRANSFER,
                    'ledgerResponse'     => $ledgerResponse,
                ]);
        }
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

        (new Payout\Core)->updatePayoutFromCreditTransfer($creditTransfer);

        try {
            Transactions::dispatch($this->mode, $creditTransfer->getId(), EntityConstants::CREDIT_TRANSFER, $ledgerResponse);
        } catch (\Throwable $ex) {
            // trace and ignore exception
            $payload = [
                'credit_transfer_id' => $creditTransfer->getId(),
                'entity_name'        => EntityConstants::CREDIT_TRANSFER,
                'ledger_response'    => $ledgerResponse,
            ];
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED, $payload);
        }
    }
}
