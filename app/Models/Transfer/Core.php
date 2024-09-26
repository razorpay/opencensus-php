<?php

namespace RZP\Models\Transfer;

use Ramsey\Uuid\Uuid;
use Razorpay\Trace\Logger;
use Neves\Events\TransactionalClosureEvent;

use App;
use RZP\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as E;
use RZP\Exception;
use RZP\Jobs\AsyncBalanceUpdateForTransfer;
use RZP\Jobs\AsyncBalanceUpdateForTransferQueueOne;
use RZP\Jobs\AsyncBalanceUpdateForTransferQueueThree;
use RZP\Jobs\AsyncBalanceUpdateForTransferQueueTwo;
use RZP\Jobs\TransferLedgerOutboxPush;
use RZP\Jobs\TransferProcessDedicatedQueueOne;
use RZP\Jobs\TransferProcessDedicatedQueueTwo;
use RZP\Jobs\TransferProcessDedicatedQueueThree;
use RZP\Jobs\TransferProcessDedicatedQueueFour;
use RZP\Jobs\TransferProcessDedicatedQueueFive;
use RZP\Jobs\TransferProcessDedicatedQueueMalaysia;
use RZP\Models\Base;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Order;
use RZP\Models\Admin;
use RZP\Trace\Tracer;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\LedgerOutbox;
use RZP\Models\Reversal as Reversal;
use RZP\Models\Payment\Refund as Refund;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\EntityOrigin;
use RZP\Jobs\TransferProcess;
use RZP\Models\Adjustment;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\Bucket;
use RZP\Jobs\TransferProcessSlice;
use RZP\Jobs\TransferProcessBatch;
use RZP\Constants\Metric as Metrics;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\TransferProcessCapitalFloat;
use RZP\Jobs\Transfers\CustomerTransfer;
use RZP\Jobs\TransferProcessKeyMerchants;
use RZP\Models\Ledger\RouteJournalEvents;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Partner\Service as PartnerService;
use RZP\Exception\SettlementStatusUpdateException;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Transfer\Payment\Core as TransferPaymentCore;
use RZP\Models\Merchant\MerchantApplications as MerchantApp;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Ledger\ReverseShadow\Transfers\Core as ReverseShadowTransfersCore;


use Throwable;

class Core extends Base\Core
{
    public $parent;

    protected $mutex;

    protected $razorx;

    protected $partner;

    const ASYNC_CUSTOMER_TRANSFER_LIVE_MODE_EXPERIMENT_ID   = 'app.customer_async_transfer_experiment_id';
    const FLAG_ASYNC_CUSTOMER_TRANSFER                      = 'async';

    const PAYMENT_ID_MUTEX_FOR_TRF_PROCESSING_EXPERIMENT   = 'payment_id_mutex_for_transfer_processing';

    protected $oauthApplicationId;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->razorx = $this->app['razorx'];

        $this->partner = $this->app['basicauth']->getPartnerMerchant();

        $this->oauthApplicationId = $this->app['basicauth']->getOAuthApplicationId();
    }

    protected function makeTransferTransaction($input, $merchant, $validator)
    {
        return $this->repo->transaction(function () use ($input, $merchant, $validator)
        {
            $transfer = $this->makeTransfer($input, $merchant, $merchant);

            $this->trace->info(
                TraceCode::TRANSFER_CREATE_SUCCESS,
                ['transfer_id' => $transfer->getId()]);

            return $transfer;
        });
    }

    function isOptimizerPaymentCheck($payment)
    {
        $isOptimizerPayment = false;

        if ($payment->hasTerminal() === true) {

            $terminalTypeArray = $payment->terminal->getType();

            if (($terminalTypeArray != null) && (in_array('optimizer', $terminalTypeArray) === true))
            {
                $isOptimizerPayment = true;
            }
        }

        return $isOptimizerPayment;
    }

    /**
     * Create a direct transfer from Merchant balance
     *
     * @param array $input
     * @param Merchant\Entity $merchant
     *
     * @return Transfer\Entity
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function createForMerchant(array &$input, Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(TraceCode::TRANSFER_CREATE_REQUEST, ['input' => $input]);

        $parentMerchant = $this->fetchAccountParentMerchant($merchant);

        $platformTransfer = false;

        if($this->isValidPlatformTransfer() === true && $merchant->getId() !== $parentMerchant->getId())
        {
             $platformTransfer = true;
        }

        if (isset($input[ToType::ACCOUNT]) === true)
        {
            $this->checkForDirectTransferFeature($parentMerchant);
        }

        // here, $merchant will be sub-merchant in case of Route+ transfer & $parentMerchant will be the partner
        $this->validateMerchantForTransfer($merchant);

        $validator = new Validator;

        $validator->validateToType($input);

        $inputArray = array($input);

        $this->addAccountFromAccountCodeIfApplicable($inputArray);

        $input = $inputArray[0];

        $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($input, $parentMerchant);

        $validator->validateInput('create', $input);

        $validator->validateCurrency($merchant,$input[Entity::CURRENCY]);

        $validator->validateTransferMaxAmount($input[Entity::AMOUNT], $merchant);

        $transfer = null;

        try
        {
            $transfer = $this->makeTransferTransaction($input, $merchant, $validator);
        }
        catch (\Throwable $ex)
        {
            // Checks if the exception is caused by db connection loss and reconnects to DB(one retry)
            // made this change as a fix for production issue SI-4668
            $causedByLostConnection = $this->app['db.connector.mysql']->checkAndReloadDBIfCausedByLostConnection($ex);

            if ($causedByLostConnection === true)
            {
                $transfer = $this->makeTransferTransaction($input, $merchant, $validator);
            }
            else
            {
                $this->trace->traceException($ex, null, TraceCode::DIRECT_TRANSFER_CREATE_EXPECTION,[]);

                $input[Transfer\Entity::PLATFORM_TRANSFER] = $platformTransfer;

                throw $ex;
            }
        }

        if ($transfer->isProcessed() === true)
        {
            $this->eventTransferProcessed($transfer);
        }

        $input[Transfer\Entity::PLATFORM_TRANSFER] = $platformTransfer;

        return $transfer;
    }

    /**
     * Create a transfer from a captured payment source
     *
     * @param Payment\Entity $payment
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param bool $asyncTransfer
     *
     * @return  Base\PublicCollection
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function createForPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant): Base\PublicCollection
    {
        $this->validateMerchantForTransfer($merchant);

        $this->validateUsingOauth($payment);

        $this->addAccountFromAccountCodeIfApplicable($input);

        $orderTransfers =new Base\PublicCollection();

        if ($payment->hasOrder() === true)
        {
            $orderTransfers = $this->repo->transfer->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::ORDER, $payment->getApiOrderId(), $this->merchant);
        }

        $paymentTransfers = $this->repo->transfer->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::PAYMENT, $payment->getId(), $this->merchant);

        $allTransfers = $orderTransfers->merge($paymentTransfers);

        $parentMerchant = $this->fetchAccountParentMerchant($merchant, null, $payment);

        foreach ($input as $transfer)
        {
            $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($transfer, $parentMerchant);
        }

        $validator = new Validator();

        $validator->merchant = $this->merchant;

        $validator->validateTransfers($payment, $input, $allTransfers);

        $totalTransferAmount = 0;

        $transfers = new Base\PublicCollection;

        $asyncTransfer = true;

        $isOptimizerPayment = $this->isOptimizerPaymentCheck($payment);

        foreach ($input as $transfer)
        {
            if ($isOptimizerPayment === true) {
                $this->trace->info(
                    TraceCode::PAYMENT_VERIFY_OPTIMIZER_CHECK,
                    [
                        'payment_id' => $payment->getId(),
                        'terminal_id' => $payment->terminal->getId(),
                    ]);

                // Handle the optimizer payment case for each transfer
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_TRANSFER_NOT_ALLOWED_FOR_OPTIMIZER_EXTERNAL_GATEWAYS);

            }

            $transfer = Tracer::inSpan(['name' => 'payment.transfer.create.make_transfer'], function() use ($transfer, $payment, $merchant, & $asyncTransfer)
            {
                return $this->makeTransfer($transfer, $payment, $merchant, $asyncTransfer);
            });

            $totalTransferAmount += $transfer['amount'];

            $transfers->push($transfer);
        }

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFERS_CREATED,
            [
                'transfer_ids' => $transfers->getIds(),
            ]
        );

        if ($asyncTransfer === false)
        {
            $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

            // Add trace log here if sync transfer flow is enabled in future.

            $input[Entity::PLATFORM_TRANSFER] = $this->isValidPlatformTransfer();

            (new Metric)->pushCreateSuccessMetrics(current($input));
        }

        return $transfers;
    }

    public function validateLinkedAccountActivationStatusAndBankVerificationStatus($input, $merchant)
    {
        $merchantDetail = null;

        if (isset($input[ToType::ACCOUNT]) === true and
            isset($input[ToType::BALANCE]) === false and
            isset($input[ToType::CUSTOMER]) === false)
        {
            $this->trace->info(TraceCode::VALIDATE_LINKED_ACCOUNT_ACTIVATION_STATUS,
            [
               'transfer_input'     => $input,
               'parent_merchant_id' => $merchant->getId()
            ]);
            $accountId = $input[ToType::ACCOUNT];

            $linkedAccount = $this->repo
                                  ->account
                                  ->findByPublicIdAndMerchant($accountId, $merchant);

            $validator = new Validator;

            $validator->validateMerchantActivationStatusAndBankVerificationStatus($linkedAccount->merchantDetail);
        }
    }

    /**
     * Create a transfer from a captured payment source
     *
     * @param Order\Entity $order
     * @param array $transferInput
     *
     * @return  Base\PublicCollection
     * @throws Exception\BadRequestException
     */
    public function createForOrder(Order\Entity $order, array $transferInput): Base\Collection
    {
        $transfers = new Base\Collection();

        $parentMerchant = $this->fetchAccountParentMerchant($this->merchant, $transferInput[Order\Entity::PUBLIC_KEY] ?? null);

        unset($transferInput[Order\Entity::PUBLIC_KEY]);

        foreach ($transferInput as $input)
        {
            $input[Entity::STATUS] = Status::CREATED;

            $input[Entity::ORIGIN] = Origin::ORDER_AUTOMATION;

            $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($input, $parentMerchant);

            if (isset($input[Entity::ACCOUNT_CODE]) === true)
            {
                $accountId = $this->repo->merchant->getIdByAccountCodeAndParent($input[Entity::ACCOUNT_CODE], $this->merchant->getId());

                $input[ToType::ACCOUNT] = Merchant\Account\Entity::getSignedId($accountId);
            }

            if (isset($input[ToType::BALANCE]) === true)
            {
                $description = 'Transfer for ' . $input[ToType::BALANCE];
                if (isset($order->getNotes()['description']) === true) {
                     $description = $order->getNotes()['description'];
                }
                $input[Entity::NOTES]['description'] = $description;
                $input[Entity::NOTES]['type'] = $input[ToType::BALANCE];
                $to = $this->repo
                    ->balance
                    ->getMerchantBalance($this->merchant);
            }
            else if (isset($input[ToType::ACCOUNT]) === true)
            {
                $to = $this->repo->account->findByPublicIdAndMerchant($input[ToType::ACCOUNT], $parentMerchant);

                // extracts linked account notes and validates.
                $this->getLinkedAccountNotes($input);
            }
            $transfer = Tracer::inSpan(['name' => 'order.transfer.create.build'], function() use ($order, $to, $input)
            {
                return $this->buildTransferEntity($order, $to, $input, $this->merchant);
            });

            $this->repo->transfer->saveOrFail($transfer);

            if($this->isValidPlatformTransfer() === true)
            {
                (new EntityOrigin\Core)->createEntityOrigin($transfer, EntityOrigin\Constants::MARKETPLACE_APPLICATION);
            }

            $transfers->push($transfer->toArrayPublic());
        }

        return $transfers;
    }

    /**
     * Fetch parent merchant of a linked account. This function handles one specific scenario where the parent
     * is partner instead of this->merchant which is a sub-merchant (X-Razorpay-Account passed in the header).
     * Currently, this use case is applicable for platform transfers under Route + Partnerships.
     *
     * @param Merchant\Entity|null $merchant
     * @return Merchant\Entity|null
     * @throws Exception\BadRequestException
     */
    public function fetchAccountParentMerchantForMarketplaceTransfer(?Merchant\Entity $merchant): Merchant\Entity | null
    {
        $partner = $this->partner;

        $merchant = $merchant ?? $this->merchant;

        (new \RZP\Models\Partner\Validator())->validateIsAggregatorOrPurePlatformPartner($partner);

        (new Merchant\WebhookV2\Validator())->validatePartnerSubMerchantMapping($partner, $merchant);

        $this->trace->info(
            TraceCode::FETCH_ROUTE_PARTNERSHIPS_PARENT_ACCOUNT,
            [
                Merchant\Entity::PARENT_ID          => $partner->getId(),
                Merchant\Constants::SUBMERCHANT_ID  => $merchant->getId(),
            ]
        );

        return $partner;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function isValidPlatformTransfer() : bool
    {
        $partner = $this->partner;

        if (empty($partner) === true or
            in_array($partner->getPartnerType(), [Merchant\Constants::AGGREGATOR, Merchant\Constants::PURE_PLATFORM]) === false or
            (empty($this->parent) === false and ($this->parent->getId() === $this->merchant->getId())) or
            (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::ROUTE_PARTNERSHIPS, $partner, $this->oauthApplicationId) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function fetchAccountParentMerchant(?Merchant\Entity $merchant, ?string $publicKey = null, Base\Entity $entity = null): ?Merchant\Entity
    {
        $this->setPartnerContextIfApplicable($publicKey, $entity);

        if ((new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::ROUTE_PARTNERSHIPS, $this->partner, $this->oauthApplicationId) === true)
        {
            $this->parent = $this->fetchAccountParentMerchantForMarketplaceTransfer($merchant);
        }

        if (isset($this->parent) === false)
        {
            $this->parent = $merchant ?? $this->merchant;
        }

        return $this->parent;
    }

    /**
     * Edit the attributes of a transfer entity
     * Currently allowed for on_hold and on_hold_until fields
     *
     * @param  Transfer\Entity $transfer
     * @param  array           $input
     *
     * @return Entity
     */
    public function edit(Transfer\Entity $transfer, array $input) : Entity
    {
        $currentOnHold = (bool)$transfer->getOnHold();
        $newOnHold = (bool)$input[Entity::ON_HOLD];

        // This flow may have a bug. Reversed transfers shouldn't be editable, check what happens.
        $transfer->edit($input);

        //
        // `on_hold` is a required attribute for PATCH request, and
        // affects the value of `on_hold_until` when not sent:
        //
        // - Sending `on_hold`=true without `on_hold_until` will
        //   reset the `on_hold_until` timestamp, basically moving
        //   the transfer to an indefinite hold state.
        // - Sending `on_hold`=false without `on_hold_until` will
        //   release the transfer for settlement
        //
        if (isset($input[Entity::ON_HOLD_UNTIL]) === false)
        {
            $transfer->setOnHoldUntil(null);
        }

        // Cannot use status = processed here since status can also be partially_reversed.
        if (($transfer->getProcessedAt() !== null) and
            ($transfer->getSettlementStatus() !== null) and
            ($currentOnHold !== $newOnHold))
        {
            $settlementStatus = ($newOnHold === true) ? SettlementStatus::ON_HOLD : SettlementStatus::PENDING;

            $transfer->setSettlementStatus($settlementStatus);
        }

        list($transfer, $payment) = $this->repo->transaction(function () use ($transfer, $input)
        {
            $payment = $this->updatePaymentHold($transfer);

            $this->repo->saveOrFail($transfer);

            $this->trace->info(
                TraceCode::TRANSFER_EDIT_SUCCESS,
                ['transfer_id' => $transfer->getId()]);

            return [$transfer, $payment];
        });

        $paymentService = new Payment\Service();
        $isExpEnabled = $paymentService->checkIfTransactionOnholdWriteRemovalEnabled($payment->merchant);

        // only if exp is not enabled keep old flow else new flow
        if ($isExpEnabled === false)
        {
            $this->dispatchForSettlementService($payment);
        }
        else
        {
            $this->dispatchForSettlementServiceNew($payment);
        }

        return $transfer;
    }

    /**
     * Creates and saves a new transfer entity
     *
     * @param Base\Entity     $source Source entity for transfer
     * @param Base\Entity     $to     Receiving entity for transfer
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    protected function createTransfer(
        Base\Entity $source,
        Base\Entity $to,
        array $input,
        Merchant\Entity $merchant) : Entity
    {
        $transfer = $this->buildTransferEntity($source, $to, $input, $merchant);

        // Create transfer transaction only if reverse shadow is not enabled or if method is customer wallet loading
        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false or
            (isset($input[ToType::CUSTOMER]) === true))
        {
            return $this->createTransactionForTransfer($transfer);
        }

        return $transfer;
    }

    protected function buildTransferEntity(
        Base\Entity $source,
        Base\Entity $to,
        array $input,
        Merchant\Entity $merchant): Entity
    {
        $transfer = new Entity;

        $transfer->generateId();

        $transfer->build($input);

        $transfer->merchant()->associate($merchant);

        $transfer->source()->associate($source);

        $transfer->to()->associate($to);

        $this->setAccountCodeIfApplicable($transfer);

        $sourceChannel = null;

        if($source instanceof Payment\Entity)
        {
            $sourceChannel = $source->getSourceChannel();
        }

        if($source instanceof Order\Entity){

            $payment = $this->fetchPaymentEntityFromOrder($source);

            if($payment !== null )
            {
                $sourceChannel = $payment->getSourceChannel();
            }
        }

        if( $sourceChannel !== null) {

            $transfer->setSourceChannel($sourceChannel);
        }

        return $transfer;
    }

    protected function setAccountCodeIfApplicable(Entity $transfer)
    {
        if (($transfer->getToType() !== Constants\Entity::MERCHANT) or
            ($transfer->getAccountCode() !== null) or
            ($this->merchant->isRouteCodeEnabled() === false))
        {
            return;
        }

        $accountCode = $this->repo->merchant->getAccountCodeById($transfer->getToId());

        if ($accountCode !== null)
        {
            $transfer->setAccountCode($accountCode);
        }
    }

    /**
     * If a transfer hold is modified, also update its corresponding payment
     * and transaction records with the new hold values
     *
     * @param  Entity $transfer
     */
    protected function updatePaymentHold(Entity $transfer)
    {
        $transferOnHold = $transfer->getOnHold();

        $transferOnHoldUntil = $transfer->getOnHoldUntil();

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_HOLD,
            [
                'transfer_id'               => $transfer->getId(),
                'transfer_on_hold'          => $transferOnHold,
                'transfer_on_hold_until'    => $transferOnHoldUntil
            ]);

        $payment = $this->repo
                        ->payment
                        ->findByTransferIdAndMerchant(
                            $transfer->getId(),
                            $transfer->getToId());

        $payment = $this->repo->payment->findOrFail($payment->getId());

        $payment->setOnHold($transferOnHold);

        $payment->setOnHoldUntil($transferOnHoldUntil);

        $txnCore = new Transaction\Core;

        $paymentService = new Payment\Service();

        $isExpEnabled = $paymentService->checkIfTransactionOnholdWriteRemovalEnabled($payment->merchant);

        // only if exp is not enabled do a write to transaction
        //Note : payment txn could be null if there is a delay in txn creation in reverse shadow mode
        if ($isExpEnabled === false)
        {
            if ($payment->transaction !== null)
            {
                $txn = $txnCore->updateOnHoldToggle($payment);

                $this->repo->saveOrFail($txn);
            }
        }

        $this->repo->saveOrFail($payment);

        return $payment;
    }

    /**
     * Called on payment transfer operation
     * Updates the value of amount_transferred in Payments
     *
     * @param  Payment\Entity $payment
     * @param  int            $amount
     *
     * @throws Exception\LogicException
     */
    public function updatePaymentAmountTransferred(Payment\Entity $payment, int $amount)
    {
        if ($payment->isTransferredInOldFlow())
        {
            $this->repo->payment->lockForUpdateAndReload($payment);

            $this->trace->info(
                TraceCode::PAYMENT_UPDATE_AMOUNT_TRANSFERRED,
                [
                    'payment_id'    => $payment->getId(),
                    'amount'        => $amount,
                ]);

            $payment->transferAmount($amount);

            $this->repo->saveOrFail($payment);

            return;
        }

        $transferPayment = (new TransferPaymentCore)->createOrFetch($payment);

        $this->repo->transfer_payment->lockForUpdateAndReload($transferPayment);

        $this->trace->info(
            TraceCode::TRANSFER_PAYMENT_UPDATE_AMOUNT_TRANSFERRED,
            [
                'payment_id'    => $payment->getId(),
                'amount'        => $amount,
            ]);

        $transferPayment->transferAmount($amount);

        $this->repo->saveOrFail($transferPayment);
    }

    /**
     * Create and process a transfer
     *
     * @param  array           $input
     * @param  Base\Entity     $source
     * @param  Merchant\Entity $merchant
     *
     * @return Transfer\Entity
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function makeTransfer(array $input, Base\Entity $source, Merchant\Entity $merchant, &$asyncTransfer = false) : Entity
    {
        $validator = new Validator;

        $validator->validateInput('create', $input);

        $validator->validateCurrency($merchant,$input[Entity::CURRENCY]);

        $transfer = null;

        if (isset($input[ToType::CUSTOMER]) === true)
        {
            $id = $input[ToType::CUSTOMER];

            // $asyncTransfer === true is being sent when performing payment transfer
            if ($asyncTransfer === true)
            {
                $asyncTransfer = $this->isAsyncCustomerTransferExperimentEnabledForMerchant($merchant->getId(), self::ASYNC_CUSTOMER_TRANSFER_LIVE_MODE_EXPERIMENT_ID, $this->mode);
            }

            return $this->customerTransfer($id, $source, $input, $merchant, $asyncTransfer);
        }
        else if (isset($input[ToType::ACCOUNT]) === true)
        {
            $id = $input[ToType::ACCOUNT];

            $input[Entity::STATUS] = Status::CREATED;

            $transfer = Tracer::inSpan(['name' => 'payment.transfer.create.make_transfer.account_transfer'], function() use ($id, $source, $input, $merchant, $asyncTransfer)
            {
                return $this->accountTransfer($id, $source, $input, $merchant, $asyncTransfer);
            });
        }

        return $transfer;
    }

    /**
     * Transfer to a customer wallet account
     *
     * @param  string               $customerId
     * @param  Base\Entity          $source
     * @param  array                $input
     * @param  Merchant\Entity      $merchant
     * @return Transfer\Entity
     */
    protected function customerTransfer(
        string $customerId,
        Base\Entity $source,
        array $input,
        Merchant\Entity $merchant,
        bool $asyncTransfer) : Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER,
            [
                'input'    => $input,
                'is_async' => $asyncTransfer
            ]);

        $this->verifyFeatureAllowed(Feature\Constants::OPENWALLET, $merchant);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($customerId, $merchant);

        if ($this->shouldUseCustomerTransferReverseShadowV2Flow($source, $asyncTransfer))
        {
            $transfer = $this->buildTransferEntity($source, $to, $input, $merchant);

            $this->repo->saveOrFail($transfer);

            (new Customer\Balance\Core)->fetchOrCreate($to, $merchant);

            (new Customer\Transaction\Core)->createForCustomerCredit($transfer,
                $transfer->getAmount(),
                $to->getId(),
                $merchant);

            (new ReverseShadowTransfersCore())->createReverseShadowEntriesForCustomerWalletLoadingV2($transfer);

            return $transfer;
        }
        else if ($asyncTransfer === true)
        {
            $transfer = Tracer::inSpan(['name' => 'payment.transfer.create.make_transfer.customer_transfer.build'], function() use ($source, $to, $input, $merchant)
            {
                return $this->buildTransferEntity($source, $to, $input, $merchant);
            });

            $transfer->setStatus(Status::CREATED);

            $this->repo->saveOrFail($transfer);

            $this->trace->info(
                TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER_QUEUE_PUSH,
                [
                    'transfer_id' => $transfer->getId(),
                    'customer_id' => $customerId,
                    'merchant_id' => $merchant->getId()
                ]);

            CustomerTransfer::dispatch($this->mode,
                [
                    'customer_id' => $customerId,
                    'merchant_id' => $merchant->getId(),
                    'transfer'    => $transfer
                ]
            );

            return $transfer;
        }
        else
        {
            // Create a transfer its corresponding txn - debits the merchant
            $transfer = $this->createTransfer($source, $to, $input, $merchant);

            // Create customer balance if it doesn't exist.
            (new Customer\Balance\Core)->fetchOrCreate($to, $merchant);

            $txn = $transfer->transaction;

            (new Customer\Transaction\Core)->createForCustomerCredit($transfer,
                $txn->getAmount(),
                $to->getId(),
                $merchant);

            $this->createLedgerEntriesForCustomerTransferReverseShadow($transfer);

            return $transfer;
        }
    }

    protected function shouldUseCustomerTransferReverseShadowV2Flow(Base\Entity $source, bool $asyncTransfer) : bool
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            return false;
        }

        if ($asyncTransfer === true)
        {
            return false;
        }

        $experimentIds = [
            $this->app['config']->get('app.customer_transfer_reverse_shadow_v2'),
        ];

        if ($this->merchant->isPostpaid() === true)
        {
            $experimentIds[] = $this->app['config']->get('app.customer_transfer_reverse_shadow_v2_postpaid');
        }

        $ledgerService = $this->app['ledger'];

        $core = (new ReverseShadowTransfersCore());

        $merchantAccountBalances = $core->getMerchantAccountBalances($ledgerService, $this->merchant->getId());

        if ($merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS] > 0)
        {
            $experimentIds[] = $this->app['config']->get('app.customer_transfer_reverse_shadow_v2_amount_credits');
        }

        $negativeLimit = $core->getMaxNegativeLimitForTransferV2();

        if ($negativeLimit > 0)
        {
            $experimentIds[] = $this->app['config']->get('app.customer_transfer_reverse_shadow_v2_negative_limit');
        }

        $experimentData = [];

        foreach ($experimentIds as $experimentId)
        {
            $id = $source->getId();

            // override id in case of balance transfers
            if ($source instanceof Merchant\Entity)
            {
                $id = Uuid::uuid1();
            }

            $experimentData[] = array(
                'id'              => strval($id),
                'experiment_id'   => $experimentId,
                'request_data'    => json_encode([
                    'merchant_id' => $this->merchant->getId(),
                ])
            );
        }

        $response = $this->app['splitzService']->bulkCallsToSplitz($experimentData);

        // check all the experiments returned enabled variant, if any of them is not ramped up, this flow should not get triggered.
        $enabled = array_reduce($response, function ($carry, $item) {
            return $carry && ($item['variant']['name'] === "enabled");
        }, true);

        $this->trace->info(
            TraceCode::CUSTOMER_TRANSFER_REVERSE_SHADOW_EXP,
            [
                'merchant_id'           => $this->merchant->getId(),
                'enabled'     => $enabled,
            ]);

        return $enabled;
    }

    public function createLedgerEntriesForCustomerTransferReverseShadow(Transfer\Entity $transfer)
    {
        $merchant = $transfer->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            return;
        }

        try
        {
            $transactionMessage = (new ReverseShadowTransfersCore())->createTransactionMessageForCustomerWalletLoading($transfer);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
            }));

            $this->trace->info(
                TraceCode::CUSTOMER_WALLET_LOADING_LEDGER_EVENT_TRIGGERED,
                [
                    'transfer_id'           => $transfer->getId(),
                    'message'               => $transactionMessage,
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PG_LEDGER_ROUTE_ENTRY_FAILED,
                [
                    'transfer_id'           => $transfer->getId(),
                ]);
        }
    }

    public function createLedgerEntriesForCustomerTransfer($transfer, Merchant\Entity $merchant)
    {
        // Shadow mode will not be used anymore
        return;

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            return;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        try
        {
            $transactionMessage = RouteJournalEvents::createTransactionMessageForCustomerWalletLoading($transfer);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
            }));

            $this->trace->info(
                TraceCode::CUSTOMER_WALLET_LOADING_LEDGER_EVENT_TRIGGERED,
                [
                    'transfer_id'           => $transfer->getId(),
                    'message'               => $transactionMessage,
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PG_LEDGER_ROUTE_ENTRY_FAILED,
                [
                    'transfer_id'           => $transfer->getId(),
                ]);
        }
    }

    /**
     * Transfer to a Marketplace account
     *
     * @param string $accountId
     * @param Base\Entity $source
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param bool $asyncTransfer
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function accountTransfer(string $accountId, Base\Entity $source, array $input, Merchant\Entity $merchant, bool $asyncTransfer): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT, ['transfer' => $input]);

        $parentMerchant = $this->fetchAccountParentMerchant($merchant, $input[Payment\Entity::PUBLIC_KEY] ?? null);

        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE, $parentMerchant);

        $to = $this->repo->account->findByPublicIdAndMerchant($accountId, $parentMerchant);

        // extracts linked account notes and validates.
        $this->getLinkedAccountNotes($input);

        $originPayment = null;

        if (($source instanceof Payment\Entity) === true)
        {
            $originPayment = $source;
        }

        $parentMerchant->getValidator()->validateMerchantForMarketplaceTransfer($to, $this->mode);

        if ($asyncTransfer === true)
        {
            $transfer = Tracer::inSpan(['name' => 'payment.transfer.create.make_transfer.account_transfer.build'], function() use ($source, $to, $input, $merchant)
            {
                return $this->buildTransferEntity($source, $to, $input, $merchant);
            });

            $transfer->setStatus(Status::PENDING);

            $this->repo->saveOrFail($transfer);

            if($this->isValidPlatformTransfer() === true)
            {
                $input[Entity::PLATFORM_TRANSFER] = true;

                (new Metric)->pushCreateSuccessMetrics($input);

                (new EntityOrigin\Core)->createEntityOrigin($transfer, EntityOrigin\Constants::MARKETPLACE_APPLICATION);
            }

            return $transfer;
        }
        else
        {
            return $this->PaymentTransferSync($source, $input, $merchant, $to, $originPayment);
        }
    }

    /**
     * Extract lanotes from transfer notes.
     * @param array $input
     *
     * @throws \RZP\Exception\BadRequestException
     * @return array
     */
    public function getLinkedAccountNotes(array $input): array
    {
        $transferNotes = $input[Entity::NOTES] ?? [];

        $laNotesKeys = $input[Entity::LINKED_ACCOUNT_NOTES] ?? [];

        $laNotes = [];

        if ((empty($laNotesKeys) === false) and (is_array($laNotesKeys) === true))
        {
            $laNotes = array_only($transferNotes, $laNotesKeys);

            (new Validator)->validateLinkedAccountNotes($laNotes, $laNotesKeys);
        }

        return $laNotes;
    }

    protected function verifyFeatureAllowed(string $feature, Merchant\Entity $merchant)
    {
        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'This transfer is not supported');
        }
    }
    public function validateUsingOauth(Payment\Entity $payment)
    {
        $entityOrigin = (new EntityOrigin\Core)->fetchEntityOriginV2($payment);

        $paymentOAuth = optional($entityOrigin)->getOriginId();

        if($this->oauthApplicationId !== $paymentOAuth){
            throw new Exception\BadRequestValidationFailureException(
                'This transfer is not supported');
        }
    }
    protected function validateMerchantForTransfer(Merchant\Entity $merchant)
    {
        $isOnHold = $merchant->getHoldFunds();

        //
        // Don't allow a transfer operation on live mode
        // if merchant funds are on hold
        //
        if (($this->mode === Constants\Mode::LIVE) and
            ($isOnHold === true))
        {
            //
            // Banks are testing our Openwallet demo app on
            // on live mode, merchant ID 5ohNv7JkUtGrRx
            // and hence we're ignoring this check for the merchant ID
            //
            if ($merchant->getId() === '5ohNv7JkUtGrRx')
            {
                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                null,
                ['merchant_id' => $merchant->getId()]);
        }
    }

    public function checkBalanceTransfer(array $transfers)
    {
        foreach($transfers as $transfer)
        {
            if (isset($transfer[Transfer\ToType::BALANCE]) === true)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function validateTransfersInput(int $orderAmount, array $transfers, $merchant)
    {
        if ($this->merchant === null)
        {
            $this->merchant = $merchant;
        }

        $this->validateMerchantForTransfer($this->merchant);

        $validator = new Validator();

        $validator->merchant = $this->merchant;

        $publicKey = $transfers[Order\Entity::PUBLIC_KEY] ?? null;

        unset($transfers[Order\Entity::PUBLIC_KEY]);

        $this->addAccountFromAccountCodeIfApplicable($transfers);

        Tracer::inSpan(['name' => 'order.transfer.validate'], function() use ($validator, $transfers, $orderAmount)
        {
            $validator->validateTransferForOrder($transfers, $orderAmount);
        });

        if ($this->checkBalanceTransfer($transfers) === true)
        {
            $validator->validateBalanceTransferChecks($transfers, $this->merchant->getId(), $orderAmount);
        }
        else
        {
            $parentMerchant = $this->fetchAccountParentMerchant($this->merchant, $publicKey);

            $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE, $parentMerchant);

            foreach ($transfers as $transfer)
            {
                $to = $this->repo->account->findByPublicIdAndMerchant($transfer[ToType::ACCOUNT], $parentMerchant);

                $parentMerchant->getValidator()->validateMerchantForMarketplaceTransfer($to, $this->mode);
            }
        }
    }

    public function getForPayment(string $paymentId, array $status = [])
    {
        return $this->repo
                    ->transfer
                    ->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::PAYMENT, $paymentId, $this->merchant, $status);
    }

    public function getForOrder(string $orderId, array $status = [])
    {
        return $this->repo
                    ->transfer
                    ->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::ORDER, $orderId, $this->merchant, $status);
    }

    public function createTransactionForTransfer($transfer, $txnId = null)
    {
        $txnCore = new Transaction\Core;

        // Create a transaction for the transfer; debits the source merchant
        list($txn,$feesSplit) = $txnCore->createFromTransfer($transfer, $txnId);

        $transfer->setFees($txn->getFee());

        $transfer->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($transfer);

        $txnCore->saveFeeDetails($txn, $feesSplit);

        return $transfer;
    }
    /**
     * @param Base\Entity $source
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Base\PublicEntity $to
     * @param Base\Entity|null $originPayment
     * @return Entity
     * @throws Exception\BadRequestException
     */
    protected function PaymentTransferSync(Base\Entity $source, array $input, Merchant\Entity $merchant, Base\PublicEntity $to, ?Base\Entity $originPayment): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_SYNC_START,
            [
                'merchant_id' => $merchant->getId()
            ]);

        $transfer = $this->createTransfer($source, $to, $input, $merchant);

        // Extract Notes from the input and sync it to payment entity.
        $laNotes = $this->getLinkedAccountNotes($input);

        $input[Transfer\Entity::NOTES] = $laNotes;

        $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $originPayment, $transfer);

        $transfer->setProcessed();

        $this->repo->saveOrFail($transfer);

        $transferPayment->transfer()->associate($transfer);

        $this->repo->saveOrFail($transferPayment);

        if($this->isValidPlatformTransfer() === true)
        {
            (new EntityOrigin\Core)->createEntityOrigin($transfer, EntityOrigin\Constants::MARKETPLACE_APPLICATION);
        }

        $totalTds = $this->calculateTds($transferPayment, $transfer);

        if($totalTds > 0)
        {
            $this->createPaymentTransferTds($transferPayment, $totalTds);
        }

        // call bulk journal creation in sync
        // If success then mark transfer as processed.
        // If it fails then halt the process and send a failure response.
        $this->createLedgerEntriesForTransferReverseShadowInSync($transfer, $transferPayment);

        (new Transfer\Core())->createLedgerEntriesForTransfer($transferPayment, $transfer->merchant);

        return $transfer;
    }

    public function calculateTds(Payment\Entity $transferPayment, Entity $transfer, Payment\Entity $payment=null)
    {
        try
        {
            // when async processing kicks in, merchant context is not set. Fetch merchant from transfer
            if($this->merchant == null)
            {
                $this->merchant = $transfer->merchant;
            }

            $parentMerchant = (new Core())->fetchAccountParentMerchant($this->merchant , $payment?->getPublicKey() ?? null, $transfer);

            $this->trace->info(TraceCode::FETCH_ROUTE_PARTNERSHIPS_PARENT_ACCOUNT, [
                'transfer_id'           => $transfer?->getId(),
                'transfer_payment_id'   => $transferPayment?->getId(),
                'parent_merchant'       => $parentMerchant?->getId(),
            ]);

            // If "partner_plat_fee_invoice" flag is disabled for partner, Partner would land in default partner-invoicing model where TDS isn't applicable.
            if (empty($parentMerchant) === true or
                in_array($parentMerchant->getPartnerType(), [Merchant\Constants::AGGREGATOR, Merchant\Constants::PURE_PLATFORM]) === false or
                (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::ROUTE_PARTNERSHIPS, $parentMerchant) === false or
                (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::PARTNER_PLAT_FEE_INVOICE, $parentMerchant) === false)
            {

                $this->trace->info(TraceCode::PAYMENT_TRANSFER_TDS_CALCULATION_SKIPPED,
                    [
                    'partner_id'            => $parentMerchant?->getId(),
                    'partner_type'          => $parentMerchant?->getPartnerType(),
                    'merchant_id'           => $this->merchant?->getId(),
                    'transfer_id'           => $transfer?->getId(),
                    'transfer_payment_id'   => $transferPayment?->getId(),
                    ]
                );

                return 0;
            }

            return $transferPayment->getAmount() * 0.05;
        }
        catch(Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PAYMENT_TRANSFER_TDS_CALCULATION_FAILED,
                [
                    'merchant_id'           => $this->merchant->getId(),
                    'transfer_id'           => $transfer->getId(),
                    'transfer_payment_id'    => $transferPayment->getId()
                ]
            );
        }
        return 0;
    }

    public function createPaymentTransferTds(Payment\Entity $transferPayment, int $totalTds)
    {
        $input = [
            Adjustment\Entity::TYPE        => Balance\Type::PRIMARY,
            Adjustment\Entity::AMOUNT      => (-1 * $totalTds), // tds should be debit
            Adjustment\Entity::CURRENCY    => Currency::INR,
            Adjustment\Entity::DESCRIPTION => 'Platform transfer TDS',
        ];

        (new Adjustment\Core)->createAdjustmentForSource($input, $transferPayment, Adjustment\Constants::PLATFORM_TRANSFER_TDS_ADJUSTMENT);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            [
                'type'          =>  'PlatformTransferTDS',
                'payment_id'    =>  $transferPayment->getId(),
                'amount'        =>  $totalTds
            ]
        );
    }


    /**
     * Used to analyse while dispatching it to settlement service
     * @param $payment
     * @throws \Throwable
     */
    public function dispatchForSettlementService($payment)
    {
        $txn = $payment->transaction;

        $bucketCore = new Bucket\Core;

        $balance = $txn->accountBalance;

        $newService = $bucketCore->shouldProcessViaNewService($txn->getMerchantId(), $balance);

        if ($newService === true)
        {
            $reason = null;

            if($payment->getOnHold() === true)
            {
                $reason = 'transfer put on hold';
            }

            $bucketCore->settlementServiceToggleTransactionHold([$txn->getId()], $reason);
        }
        else
        {
            (new Transaction\Core)->dispatchForSettlementBucketing($txn);
        }
    }

    public function dispatchForSettlementServiceNew($payment)
    {
        $bucketCore = new Bucket\Core;

        $reason = null;

        if($payment->getOnHold() === true)
        {
            $reason = 'transfer put on hold';
        }

        $bucketCore->settlementServiceToggleTransactionHold([$payment->getTransactionId()], $reason);
    }

    protected function addAccountFromAccountCodeIfApplicable(array & $transfers)
    {
        $flag = false;

        foreach ($transfers as & $transfer)
        {
            (new Validator())->validateToType($transfer);

            if (isset($transfer[Entity::ACCOUNT_CODE]) === true)
            {
                $accountCode = $transfer[Entity::ACCOUNT_CODE];

                if ($flag === false)
                {
                    $this->isAccountCodeAllowed($accountCode);

                    $flag = true;
                }

                (new Validator())->validateAccountCode(Entity::ACCOUNT_CODE, $accountCode);

                $accountId = $this->repo->merchant->getIdByAccountCodeAndParent($accountCode, $this->merchant->getId());

                if ($accountId === null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_CODE,
                        Entity::ACCOUNT_CODE,
                        $accountCode,
                        $accountCode . ' is an invalid account_code.'
                    );
                }

                $transfer[ToType::ACCOUNT] = Merchant\Account\Entity::getSignedId($accountId);
            }
        }
    }

    public function isAccountCodeAllowed(string $accountCode)
    {
        if ($this->merchant->isRouteCodeEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_CODE_NOT_ENABLED,
                Entity::ACCOUNT_CODE,
                $accountCode,
                'account_code is not allowed for this merchant.'
            );
        }
    }

    public function eventTransferProcessed(Entity $transfer)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $transfer
        ];

        $this->app['events']->dispatch('api.transfer.processed', $eventPayload);
    }

    public function eventTransferFailed(Entity $transfer)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $transfer
        ];

        $this->app['events']->dispatch('api.transfer.failed', $eventPayload);
    }

    protected function checkForDirectTransferFeature(Merchant\Entity $merchant)
    {
        if ($merchant->hasDirectTransferFeature() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DIRECT_TRANSFER_FEATURE_NOT_ENABLED,
                null,
                [
                    'merchant_id' => $merchant->getId(),
                ],
                'This feature is not enabled for this merchant.'
            );
        }
    }

    public function fetchTransfersAndIncrementAttempts(Order\Entity $order)
    {
        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourceTypeAndIdAndMerchant(Constant::ORDER,  $order->getId(), $order->merchant , [Status::FAILED]);

        if (empty($transfers) === true)
        {
            return;
        }

        $transfers = $transfers->where(Entity::ATTEMPTS, '<', Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS);

        $transfers->callOnEveryItem('incrementAttempts');

        $this->repo->saveOrFailCollection($transfers);
    }

    public function fetchTransfersAndMoveToPending(Order\Entity $order)
    {
        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourceTypeAndIdAndMerchant(Constant::ORDER,  $order->getId(), $order->merchant , [Status::CREATED]);

        if (empty($transfers) === true)
        {
            return;
        }

        $transfers = $transfers->where(Entity::ATTEMPTS, '<', Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS);

        $transfers->callOnEveryItem('setPending');

        $this->repo->saveOrFailCollection($transfers);
    }

    /**
     * @throws SettlementStatusUpdateException
     */
    public function updateSettlementStatusInTransfers(string $settlementId)
    {
        $transferIds = $this->repo->transfer->getIdsByRecipientSettlementId($settlementId, Status::$forSettlementStatusUpdate);

        $this->traceTransferIdsFetchedForSettlementStatusUpdate($settlementId, $transferIds);

        $totalTransfersAmount = 0;

        foreach ($transferIds as $transferId)
        {
            $transfer = $this->repo->transfer->findOrFail($transferId);

            $totalTransfersAmount += $transfer->getAmount();

            if ($transfer->getSettlementStatus() === SettlementStatus::SETTLED)
            {
                // Skip the status update here as the status would have been updated by a previous attempt
                // of TransferSettlementStatus job.
                continue;
            }

            $transfer->setSettlementStatus(SettlementStatus::SETTLED);

            $transfer->saveOrFail();
        }

        $this->checkIfTransfersAmountMatchesSettlementAmount($settlementId, $totalTransfersAmount);

        $this->trace->info(
            TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFERS_SUCCESS,
            [
                'settlement_id' => $settlementId,
                'count'         => count($transferIds),
            ]
        );
    }

    /**
     * @throws SettlementStatusUpdateException
     */
    protected function checkIfTransfersAmountMatchesSettlementAmount($settlementId, $totalTransfersAmount)
    {
        $settlement = $this->repo->settlement->findOrFail($settlementId);

        if ($settlement->getAmount() === $totalTransfersAmount)
        {
            $this->trace->info(
                TraceCode::TRANSFERS_AMOUNT_AND_SETTLEMENT_AMOUNT_MATCHED,
                [
                    'settlement_id' => $settlementId,
                    'total_amount'  => $totalTransfersAmount,
                ]
            );

            return null;
        }

        $this->trace->error(
            TraceCode::TRANSFERS_AMOUNT_AND_SETTLEMENT_AMOUNT_MISMATCH,
            [
                'settlement_id' => $settlementId,
                'total_amount'  => $totalTransfersAmount,
            ]
        );

        throw new Exception\SettlementStatusUpdateException('Failed for settlementID: ' . $settlementId);
    }

    public function trackTransferProcessingTime(Entity $transfer, Payment\Entity $payment = null)
    {
        $transfer->reload();

        if (($transfer->isProcessed() === true) and
            ($transfer->getAttempts() === 1))
        {
            $sourceType = $transfer->getSourceType();

            if ($sourceType === Constant::PAYMENT)
            {
                $processingTime = $transfer->getProcessedAt() - $transfer->getCreatedAt();
            }
            else if ($sourceType === Constant::ORDER)
            {
                $processingTime = $transfer->getProcessedAt() - $payment->getCapturedAt();
            }

            $this->trace->info(
                TraceCode::TRANSFER_PROCESSING_TIME,
                [
                    'transfer_id'       => $transfer->getPublicId(),
                    'source_type'       => $sourceType,
                    'processing_time'   => $processingTime,
                ]
            );

            $merchant = $transfer->merchant;

            $category = $merchant->getCategory();

            $isCapitalFloatOrSliceRouteMerchant = (($merchant->isCapitalFloatRouteMerchant() === true) or
                                                   ($merchant->isSliceRouteMerchant() === true));

            $isSyncProcessingEnabled = $this->isSyncProcessingEnabled($transfer->merchant);

            if ($this->app['api.route']->getCurrentRouteName() === 'payment_transfer_batch')
            {
                (new Metric())->pushTransferProcessingBatchTimeMetrics($sourceType, $processingTime);
            }
            else if (($isCapitalFloatOrSliceRouteMerchant === true) and
                     ($this->isLiveMode() === true))
            {
                (new Metric())->pushTransferProcessingTimeMetricsForCfAndSl($sourceType, $processingTime, $isSyncProcessingEnabled);
            }
            else
            {
                (new Metric())->pushTransferProcessingTimeMetrics($sourceType, $processingTime, $category, $isSyncProcessingEnabled);
            }
        }
    }

    public function parseAttributesForTransferReversalBatch(array & $input)
    {
        // If amount is not passed, we are supposed to reverse the entire transfer amount.
        // In this case, if amount column is left empty in the file, batch service will
        // pass empty string for amount. Hence we are unsetting the amount attribute here.
        if($input['amount'] === "")
        {
            unset($input['amount']);
        }

        $this->parseNotesForBatch($input);
    }

    public function parseNotesForBatch(array & $input)
    {
        $this->jsonDecodeNotes($input, Entity::NOTES, true, TraceCode::NOTES_ATTRIBUTE_NOT_JSON);

        $this->jsonDecodeNotes($input, Entity::LINKED_ACCOUNT_NOTES, false, TraceCode::LINKED_ACCOUNT_NOTES_ATTRIBUTE_NOT_ARRAY);
    }

    protected function jsonDecodeNotes(array & $input, string $key, bool $associative, string $traceCode)
    {
        if(empty($input[$key]) === false)
        {
            try
            {
                $input[$key] = json_decode($input[$key], $associative);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    $traceCode,
                    [
                        $key => $input[$key],
                    ]
                );

                unset($input[$key]);
            }
        }
        else
        {
            unset($input[$key]);
        }
    }

    public function dispatchForTransferProcessing(string $sourceType, Payment\Entity $payment, int $delaySecs = 0, bool $isReverseShadowTxnCreate = false, array $transferInput = [])
    {
        $merchant = $payment->merchant;

        $useLedgerOutboxPushQueue = false;

        if (($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true) ||
            ($merchant->isFeatureEnabled(Feature\Constants::TRANSFER_ON_HOLD) === true))
        {
            $useLedgerOutboxPushQueue = true;
        }

        if (($sourceType === Constant::PAYMENT) and
            ($useLedgerOutboxPushQueue === false) and
            (($merchant->isFeatureEnabled(Feature\Constants::ASYNC_BALANCE_UPDATE) === true) or
                ($merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === true)))
        {
            $delaySecs = 15 * 60; // 15 minutes
        }

        if (($sourceType === Constant::ORDER) and
            ($payment->isExternal() === true))
        {
            $delaySecs = 5 * 60; // 5 minutes
        }

        try
        {
            $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::ROUTE_TRANSFER_QUEUE_CONFIG]) ?? [];
        }
        catch (\Throwable $ex)
        {
            // Fallback to other queues if an error occurs
            $config = [];
        }

        if (in_array($merchant->getId(), $config[Constant::DEDICATED_QUEUE_ONE] ?? []) === true)
        {
            TransferProcessDedicatedQueueOne::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        if (in_array($merchant->getId(), $config[Constant::DEDICATED_QUEUE_TWO] ?? []) === true)
        {
            TransferProcessDedicatedQueueTwo::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if (in_array($merchant->getId(), $config[Constant::DEDICATED_QUEUE_THREE] ?? []) === true)
        {
            TransferProcessDedicatedQueueThree::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if (in_array($merchant->getId(), $config[Constant::DEDICATED_QUEUE_FOUR] ?? []) === true)
        {
            TransferProcessDedicatedQueueFour::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if (in_array($merchant->getId(), $config[Constant::DEDICATED_QUEUE_FIVE] ?? []) === true)
        {
            TransferProcessDedicatedQueueFive::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if ($merchant->getCountry() === 'MY' )
        {
            TransferProcessDedicatedQueueMalaysia::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if ($useLedgerOutboxPushQueue === true)
        {
            $delaySecs = 5 * 60; // 5 minutes

            TransferLedgerOutboxPush::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if ($this->app['api.route']->getCurrentRouteName() === 'payment_transfer_batch')
        {
            TransferProcessBatch::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if (($merchant->isSliceRouteMerchant() === true) and
            ($this->isLiveMode() === true))
        {
            TransferProcessSlice::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }
        else if ($merchant->isRouteKeyMerchant() === true)
        {
            TransferProcessKeyMerchants::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

            return;
        }

        //
        // Live mode check is required because the Slice queue does not exist on test mode.
        //
        if ($this->isLiveMode() === true)
        {
            $selector = rand(1, 2);

            switch ($selector)
            {
                case 1:
                {
                    TransferProcess::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

                    return;
                }

                case 2:
                {
                    TransferProcessSlice::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

                    return;
                }

                default:
                {
                    $this->trace->info(
                        TraceCode::TRANSFER_PROCESS_QUEUE_SELECTOR_INVALID_VALUE,
                        [
                            'selector' => $selector,
                        ]
                    );

                    TransferProcess::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);

                    return;
                }
            }
        }

        TransferProcess::dispatch($this->mode, $payment->getId(), $sourceType, $isReverseShadowTxnCreate, $transferInput)->delay($delaySecs);
    }

    public function  createTransferTransactionsInReverseShadow($sourcePayment, array $transferInput)
    {
        $transferPublicId = $transferInput[LedgerConstants::TRANSFER_ID];

        $this->mutex->acquireAndRelease(
            'reverse_shadow_txn_' . $transferPublicId,
            function () use ($transferPublicId, $transferInput, $sourcePayment)
            {
                $this->repo->transaction(function() use ($transferPublicId, $sourcePayment, $transferInput)
                {
                    $this->trace->info(
                        TraceCode::TRANSFER_CREATE_TRANSACTION_REQUEST_REVERSE_SHADOW,
                        [
                            'transfer_input' => $transferInput,
                        ]
                    );

                    $transfer = $this->repo->transfer->findByPublicId($transferPublicId);

                    $transferPayment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());

                    $transferPayment = $this->repo->payment->findOrFail($transferPayment->getId());

                    $oldTransfer = clone $transfer;

                    // check if transfer debit txn exists
                    if ($oldTransfer->hasTransaction() !== true)
                    {
                        // create debit  transaction with source as transfer
                        $transfer = Tracer::inSpan(['name' => 'transfer.process.create_transfer_transaction'], function () use ($oldTransfer, $transferInput)
                        {
                            return $this->createTransactionForTransfer($oldTransfer, $transferInput[LedgerConstants::DEBIT_TRANSACTION_ID]);
                        });
                    }

                    // return if transferPayment credit txn exists
                    if ($transferPayment->hasTransaction() === true)
                    {
                        return;
                    }

                    //create credit txn with source as transfer payment
                    $txnCore = new Transaction\Core;

                    list($creditTxn, $feesSplit) = $txnCore->createFromPaymentTransferred($transferPayment, $transferInput[LedgerConstants::CREDIT_TRANSACTION_ID]);

                    $this->repo->saveOrFail($creditTxn);

                    $transferPayment->setTax($creditTxn->getTax());

                    if ($transferPayment->merchant->isFeeBearerCustomer() === false) // which merchant
                    {
                        //set and fee values from txn
                        $transferPayment->setFee($creditTxn->getFee());
                    }

                    $txnCore->saveFeeDetails($creditTxn, $feesSplit);

                    $this->repo->saveOrFail($transferPayment);

                    // Metric to calculate latency to track delay in transaction creation.
                    $currentTimeInSec = (int)(microtime(true));

                    $latency = $currentTimeInSec - $transfer->getProcessedAt();

                    (new Metric())->pushTransferTransactionCreationDelayMetrics($latency, $transfer->merchant->getCategory(),  $transfer->getSourceType());


                    $this->trace->info(TraceCode::PG_LEDGER_CREATE_TRANSACTION_SUCCESS,
                        [
                            LedgerConstants::DEBIT_TRANSACTION_ID => $transferInput[LedgerConstants::DEBIT_TRANSACTION_ID],
                            LedgerConstants::CREDIT_TRANSACTION_ID => $creditTxn->getId(),
                            LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER,
                            LedgerConstants::TRANSACTOR_ID => $transfer->getId(),
                            LedgerOutboxConstants::SOURCE => $transferInput[LedgerOutboxConstants::SOURCE]
                        ]
                    );

                    $this->trace->count(Metrics::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                        LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER,
                        LedgerOutboxConstants::SOURCE => $transferInput[LedgerOutboxConstants::SOURCE]
                    ]);
                });
            },
            900, ErrorCode::BAD_REQUEST_TRANSFER_TXN_CREATION_PROCESS_IN_PROGRESS);
    }

    protected function traceTransferIdsFetchedForSettlementStatusUpdate(string $settlementId, array $transferIds)
    {
        $transferIdChunks = array_chunk($transferIds, 1000);

        foreach ($transferIdChunks as $transferIdChunk)
        {
            $this->trace->info(
                TraceCode::TRANSFER_IDS_FETCHED_FOR_SETTLEMENT_STATUS_UPDATE,
                [
                    'settlement_id' => $settlementId,
                    'transfer_ids'  => $transferIdChunk,
                ]
            );
        }
    }

    public function getTransferInput(Entity $transfer)
    {
        $sourceId = Payment\Entity::getSignedId($transfer->getSourceId());

        $input = [
            ToType::ACCOUNT                 => Merchant\Account\Entity::getSignedId($transfer->getToId()),
            Entity::AMOUNT                  => $transfer->getAmount(),
            Entity::CURRENCY                => $transfer->getCurrency(),
            Entity::ON_HOLD                 => $transfer->getOnHold(),
        ];

        if (empty($transfer->getOnHoldUntil()) === false)
        {
            $input[Entity::ON_HOLD_UNTIL] = $transfer->getOnHoldUntil();
        }

        $notes = [];

        if (empty($transfer->getNotes()) === false)
        {
            $notes = $transfer->getNotes()->toArray();

            if (isset($notes[Entity::LINKED_ACCOUNT_NOTES]) === true)
            {
                unset($notes[Entity::LINKED_ACCOUNT_NOTES]);
            }
        }

        $laNotes = $transfer->getLinkedAccountNotes();

        if (empty($notes) === false)
        {
            $input[Entity::NOTES] = $notes;
        }

        if (empty($laNotes) === false)
        {
            $input[Entity::LINKED_ACCOUNT_NOTES] = $laNotes;
        }

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_RETRY_INPUT,
            [
                'transfer_id'   => $transfer->getId(),
                'source_id'     => $sourceId,
                'input'         => $input,
            ]
        );

        $input = [
            'transfers' => array($input),
        ];

        return [$sourceId, $input, $transfer->getMerchantId()];
    }

    public function createLedgerEntriesForTransfer($payment, Merchant\Entity $merchant)
    {
        if (isset($payment) === false)
        {
            return;
        }

        if (($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false) or ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true))
        {
            return;
        }

        $paymentMerchant = $this->repo->merchant->findOrFailPublic($payment->getMerchantId());

        if (($paymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false) or ($paymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true))
        {
            return;
        }

        try
        {
            $transactionMessage = RouteJournalEvents::createBulkTransactionMessageForRoute($payment);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage, true);
            }));

            $this->trace->info(
                TraceCode::TRANSFER_LEDGER_EVENT_TRIGGERED,
                [
                    'transfer_id'           => $payment->getTransferId(),
                    'payment_id'            => $payment->getId(),
                    'message'               => $transactionMessage,
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PG_LEDGER_ROUTE_ENTRY_FAILED,
                [
                    'transfer_id'           => $payment->getTransferId(),
                    'payment_id'            => $payment->getId()
                ]);
        }
    }

    public function createReverseShadowLedgerEntriesForOrderAndPaymentTransfer($transfer, $transferPaymentMerchant)
    {
        if (isset($transferPaymentMerchant) === false)
        {
            return;
        }

        $transferMerchant = $this->repo->merchant->findOrFail($transfer->getMerchantId());

        if ( ($transferMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            or ($transferPaymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false))
        {
            return;
        }

        [$fee, $tax] = (new ReverseShadowTransfersCore())->saveOrderAndPaymentTransferReverseShadowLedgerEntriesToOutbox($transfer, $transferPaymentMerchant);

        $this->trace->info(TraceCode::TRANSFER_LEDGER_ENTRIES_OUTBOX_PUSH_SUCCESS, [
            LedgerConstants::TRANSFER_ID => $transfer->getId(),
            LedgerConstants::LINKED_ACCOUNT_MERCHANT_ID => $transferPaymentMerchant->getId(),
            LedgerConstants::MERCHANT_ID => $transfer->getMerchantId(),
        ]);

        return $transfer;

    }

    private function setPartnerContextIfApplicable(?string $publicKey, ?Base\Entity $entity)
    {
        $this->trace->debug(
            TraceCode::SET_PARTNER_CONTEXT_IF_APPLICABLE,
            [
                Merchant\Constants::PUBLIC_KEY       => $publicKey,
                Entity::ID                           => $entity?->getId(),
            ]
        );

        if (empty($this->partner) === false)
        {
            return;
        }

        $application = null;

        // if public key is not available for a payment then fetch application from payment entity origin
        // note: during testing for card payments, it was found that public_key was not getting set, so this
        // is a fix for such scenarios
        if(empty($publicKey) === false)
        {
            $application = (new EntityOrigin\Core())->getOriginEntityFromPublicKey($publicKey);
        }

        if (empty($application)===true)
        {
            if (empty($entity) === false and $entity->getEntityName() === Constants\Entity::PAYMENT)
            {
                $paymentOrigin = $entity->entityOrigin;

                $origin = optional($paymentOrigin)->origin;

                $originType = optional($origin)->getEntityName();

                if ($originType === EntityOrigin\Constants::APPLICATION)
                {
                    $application = $origin;
                }
            }
            else if(empty($entity) === false and $entity->getEntityName() === Constants\Entity::TRANSFER)
            {
                try
                {
                    $entityOrigin =  (new EntityOrigin\Repository)->fetchByEntityTypeAndEntityId(Constants\Entity::TRANSFER, $entity->getId());

                    if (empty($entityOrigin) === false and $entityOrigin[EntityOrigin\Entity::ORIGIN_TYPE] === EntityOrigin\Constants::MARKETPLACE_APPLICATION)
                    {
                        $appId = $entityOrigin[EntityOrigin\Entity::ORIGIN_ID];

                        $application =  (new EntityOrigin\Core())->fetchEntityByType(EntityOrigin\Constants::APPLICATION, $appId);

                        $this->trace->info(
                            TraceCode::SET_PARTNER_CONTEXT_FROM_ENTITY,
                            [
                                Merchant\Constants::PUBLIC_KEY       => $publicKey,
                                Merchant\Constants::PARTNER_ID       => $application->getMerchantId(),
                                Merchant\Constants::APPLICATION_ID   => $application->getId()
                            ]
                        );
                    }
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException($ex, Logger::ERROR, TraceCode::SET_PARTNER_CONTEXT_FROM_ENTITY_FAILED, [
                        EntityOrigin\Entity::ENTITY_TYPE => Constants\Entity::TRANSFER,
                        Entity::ID                       => $entity?->getId(),
                    ]);
                }
            }
        }

        // fetch and set partner context from the application
        // If it's a pure platform partner then set OAuth application id
        if (empty($application) === false)
        {
            $this->partner = (new Merchant\Core())->getPartnerFromApp($application);

            if (empty($this->partner) === false and $this->partner->isPurePlatformPartner() === true)
            {
                $this->oauthApplicationId = $application->getId();
            }

            $this->trace->info(
                TraceCode::PARTNER_CONTEXT_FOR_PLATFORM_TRANSFER_SET,
                [
                    Merchant\Constants::PUBLIC_KEY       => $publicKey,
                    Merchant\Constants::PARTNER_ID       => $application->getMerchantId(),
                    Merchant\Constants::APPLICATION_ID   => $application->getId()
                ]
            );
        }
    }

    public function createTdsReversal(Payment\Entity $transferPayment, int $refundAmount)
    {
        $parentMerchant = (new Core())->fetchAccountParentMerchant($this->merchant, $transferPayment?->getPublicKey() ?? null);

        if (empty($parentMerchant) === true or
            in_array($parentMerchant->getPartnerType(), [Merchant\Constants::AGGREGATOR, Merchant\Constants::PURE_PLATFORM]) === false or
            (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::ROUTE_PARTNERSHIPS, $parentMerchant) === false)
        {
            return;
        }

        $adjustment = $this->repo->adjustment->findAdjustmentByEntityIdAndEntityType($transferPayment->getId(), E::PAYMENT, $transferPayment->merchant->getId());

        if ($adjustment !== null && $adjustment->count() >0)
        {
            $adjustment = $adjustment[0];
            $this->trace->info(
                TraceCode::ADJUSTMENT_REVERSE_CREATE_SUCCESS,
                [
                    'type'   => gettype($adjustment),
                    'rev'    => $adjustment,
                    'pay_id' => $transferPayment->getId(),
                ]);

            $request = [
                Adjustment\Entity::AMOUNT      => (int) ($refundAmount * 0.05),
                Adjustment\Entity::CURRENCY    => $transferPayment->getCurrency(),
                Adjustment\Entity::DESCRIPTION => 'Reverse adjustment for '. $adjustment->getId()
            ];

            $revAdj = (new Adjustment\Core)->createAdjustment($request, $transferPayment->merchant);

            $this->trace->info(
                TraceCode::ADJUSTMENT_REVERSE_CREATE_SUCCESS,
                [
                    'type'                      => 'PlatformTransferTDS',
                    'original_payment_id'       => $transferPayment->getId(),
                    'original_adj_id'           => $adjustment->getId(),
                    'adj_id'                    => $revAdj->getId(),
                    'amount'                    => $revAdj->getAmount()
                ]
            );
        }
    }

    public function updateBalanceAsyncForTransferTxn($transfer)
    {
        try
        {
            $transaction = $this->repo->transaction->findByEntityId($transfer->getId(), $transfer->merchant);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_TXN_NOT_FOUND,
                [
                    'message'          => 'Transaction not found',
                    'transfer_id'      => $transfer->getId(),
                ]
            );

            throw $ex;
        }

        if ($transaction->isBalanceUpdated() === true || $transaction->getBalanceId() !== null)
        {
            $this->trace->info(TraceCode::TRANSACTION_BALANCE_ALREADY_UPDATED,
                [
                    'transfer_id'      => $transfer->getId(),
                    'transaction_id'   => $transaction->getId(),
                ]
            );

            return;
        }

        $txnCore = (new Transaction\Core());

        $merchantBalance = $this->repo->balance->getMerchantBalance($transfer->merchant);

        $transfer->getValidator()->validateMerchantBalanceForTransfer($transfer->merchant, $merchantBalance);

        $txnCore->updateCredits($transaction, $transfer);

        $txnCore->updateBalances($transaction, false, true);

        $transaction->setBalanceUpdated(true);

        $this->repo->saveOrFail($transaction);

        $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_TXN_SUCCESSFUL,
            [
                'transfer_id'      => $transfer->getId(),
                'transfer_status'  => $transfer->getStatus(),
            ]
        );
    }

    public function updateBalanceAsyncForTransferPaymentTxn($transfer)
    {
        try
        {
            $transferPayment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PAYMENT_NOT_FOUND,
                [
                    'message'          => 'Transaction not found',
                    'transfer_id'      => $transfer->getId(),
                ]
            );

            throw $ex;
        }

        try
        {
            $transaction = $this->repo->transaction->findByEntityId($transferPayment->getId(), $transferPayment->merchant);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_PAYMENT_TXN_NOT_FOUND,
                [
                    'message'          => 'Transaction not found',
                    'transfer_id'      => $transfer->getId()
                ]
            );

            throw $ex;
        }

        if ($transaction->isBalanceUpdated() === true || $transaction->getBalanceId() !== null)
        {
            $this->trace->info(TraceCode::TRANSACTION_BALANCE_ALREADY_UPDATED,
                [
                    'transfer_id'      => $transfer->getId(),
                    'la_payment_id'    => $transferPayment->getId(),
                    'transaction_id'   => $transaction->getId(),
                ]
            );

            return;
        }

        $txnCore = (new Transaction\Core());

        $txnCore->updateCredits($transaction, $transferPayment);

        $txnCore->updateBalances($transaction, false, true);

        $transaction->setBalanceUpdated(true);

        $this->repo->saveOrFail($transaction);

        $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_PAYMENT_TXN_SUCCESSFUL,
            [
                'transfer_id'      => $transfer->getId(),
                'transfer_status'  => $transfer->getStatus(),
            ]
        );
    }

    private function isSyncProcessingEnabled($merchant)
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::ENABLE_TRANSFER_SYNC_PROCESSING_VIA_API,
            $this->mode
        );

        return ($variant === 'on');
    }

    public function createInternalTransactionForTransfer(array $input)
    {
        $transferId = $input["transfer_id"];

        $transferJournalId = $input['transfer_journal_id'];

        $paymentJournalId = $input['payment_journal_id'];

        app('request.ctx')->setLedgerDualWriteFlow(true);

        $transfer = $this->repo->transfer->findOrFailPublic($transferId);

        [$creditJournal, ] = $this->fetchJournalsFromLedgerForTransfer($transfer, $transfer->getToId());

        [, $debitJournal] = $this->fetchJournalsFromLedgerForTransfer($transfer, $transfer->getId());

        $this->repo->transaction(
            function () use ($transfer, $debitJournal, $creditJournal, $transferJournalId, $paymentJournalId) {
                $transferPayment = $this->repo->payment->findByTransferIdAndMerchant(
                    $transfer->getId(), $transfer->getToId());

                $reverseShadowCore = new ReverseShadowTransfersCore();

                $transferTxn = $reverseShadowCore->createTransferTransactionFromLedgerJournal(
                    $debitJournal, $transfer, false);

                $transferPaymentTxn = $reverseShadowCore->createTransferPaymentTransactionFromLedgerJournal(
                    $creditJournal, $transferPayment, false);

                if ($transferJournalId !== $transferTxn->getId())
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Debit journal ID does not match');
                }

                if ($paymentJournalId !== $transferPaymentTxn->getId())
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Credit journal ID does not match');
                }

                if ($this->isNssStreamingViaRouteMicroserviceEnabled($transfer->getMerchantId(), $this->mode))
                {
                    $txnCore = (new Transaction\Core());

                    $txnCore->dispatchForSettlementBucketing($transferTxn);

                    $txnCore->dispatchForSettlementBucketing($transferPaymentTxn);
                }

                (new Transfer\Core())->dispatchForAsyncBalanceUpdate($transfer);
            }
        );

        $this->trace->info(TraceCode::TRANSFER_TXN_API_WRITE_SUCCESS, [
            'transfer_id'     => $transferId,
            'transfer_txn_id' => $transferJournalId,
            'payment_txn_id'  => $paymentJournalId,
        ]);

        return true;
    }

    public function fetchJournalFromLedgerForTransfer(Transfer\Entity $transfer, string $merchant)
    {
        $requestHeaders = [
            Ledger\Base::LEDGER_TENANT_HEADER => 'PG',
        ];

        $ledgerInput = [
            Ledger\Base::TRANSACTOR_ID    => $transfer->getPublicId(),
            Ledger\Base::MERCHANT_ID      => $merchant,
            Ledger\Base::TRANSACTOR_EVENT => LedgerConstants::TRANSFER,
        ];

        $response = $this->app['ledger']->fetchByTransactor($ledgerInput, $requestHeaders, true);

        return $response;
    }

    public function fetchJournalIdFromLedgerForTransfer(Transfer\Entity $transfer, string $merchant)
    {
        try
        {
            $journal = $this->fetchJournalFromLedgerForTransfer($transfer, $merchant);
        }
        catch (\RZP\Exception\BaseException $ex)
        {
            $exceptionData = $ex->getData();

            // If no journal found
            if (str_contains($exceptionData['response_body']['msg'], 'record_not_found'))
            {
                return [null, null];
            }
            else
            {
                throw $ex;
            }
        }

        return (new LedgerOutbox\Core)->determineJournalIdForAPITransaction($journal, "merchant_balance", "merchant_balance");
    }

    public function fetchJournalsFromLedgerForTransfer(Transfer\Entity $transfer, string $merchant)
    {
        try
        {
            $journal = $this->fetchJournalFromLedgerForTransfer($transfer, $merchant);
        }
        catch (\RZP\Exception\BaseException $ex)
        {
            $exceptionData = $ex->getData();

            // If no journal found
            if (str_contains($exceptionData['response_body']['msg'], 'record_not_found'))
            {
                return [null, null];
            }
            else
            {
                throw $ex;
            }
        }

        $ledgerOutboxCore = new LedgerOutbox\Core;

        $debitJournals = array_filter($journal, function($item) use ($ledgerOutboxCore) {
            return $ledgerOutboxCore->filterByFundAccountTypeAndEntryType($item, LedgerConstants::MERCHANT_BALANCE, LedgerConstants::ENTRY_TYPE_DEBIT);
        });

        $creditJournals = array_filter($journal, function($item) use ($ledgerOutboxCore) {
            return $ledgerOutboxCore->filterByFundAccountTypeAndEntryType($item, LedgerConstants::MERCHANT_BALANCE, LedgerConstants::ENTRY_TYPE_CREDIT);
        });

        $this->trace->info(TraceCode::MISSING_MERCHANT_ID_LEDGER_ENTRIES,
            [
                '$creditJournals'               => $creditJournals,
            ]);

        return [$creditJournals['body'], $debitJournals['body']];
    }

    public function fetchJournalIdFromLedgerForTransferReversal(string $publicReversalId, string $merchantId )
    {
        $requestHeaders = [
            Ledger\Base::LEDGER_TENANT_HEADER => 'PG',
        ];

        $ledgerInput = [
            Ledger\Base::TRANSACTOR_ID    => $publicReversalId,
            Ledger\Base::MERCHANT_ID      => $merchantId,
            Ledger\Base::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
        ];

        $response = $this->app['ledger']->fetchByTransactor($ledgerInput, $requestHeaders, true);

        return $response['body']["id"];
    }

    public function createTransactionForTransferViaCron($transferIds)
    {
        $transfersProcessed = [];

        foreach ($transferIds as $transferId)
        {
            try
            {
                $transfer = $this->repo->transfer->findOrFailPublic($transferId);

                $merchantId = $transfer['merchant_id'];

                $merchant = (new Merchant\Repository)->findOrFailPublic($merchantId);

                [, $debitJournalId] = $this->fetchJournalIdFromLedgerForTransfer($transfer, $transfer->getMerchantId());

                [$creditJournalId,] = $this->fetchJournalIdFromLedgerForTransfer($transfer, $transfer->getToId());

                $input = [
                    LedgerConstants::DEBIT_TRANSACTION_ID  => $debitJournalId,
                    LedgerConstants::CREDIT_TRANSACTION_ID => $creditJournalId,
                    LedgerConstants::TRANSFER_ID           => $transfer->getPublicId(),
                ];

                $this->createTransferTransactionsInReverseShadow(null, $input);

                $transfersProcessed[] = $transferId;
            }
            catch (\Exception $ex)
            {
                (new Metric())->pushMetricForTransferTransactionsCreate($ex);

                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::FAILED_TRANSACTION_FOR_TRANSFERS_VIA_CRON
                );

                $this->trace->info(TraceCode::FAILED_TRANSACTION_FOR_TRANSFERS_VIA_CRON,
                                   [
                                       'id'             => $transferId,
                                       'failure_reason' => $ex,
                                   ]
                );
            }
        }

        return $transfersProcessed;
    }

    public function createTransferReversalTransactions(array $input)
    {
        $isRearchRefund = $input["is_rearch_refund"];

        $reversals = $input["reversals"];

        $customerRefundId =  $input["customer_refund_id"] ?? "";

        $customerRefund = null;

        $customerRefundJournalId = "";

        if($customerRefundId !== "")
        {
            if($isRearchRefund)
            {
                $customerRefund = (new Refund\Repository())->fetchExternalRefundById($customerRefundId, '', [], true);
            }
            else
            {
                $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
                    'method'       => 'createTransferReversalTransactions',
                ]);
                $customerRefund = $this->repo->refund->findOrFail($customerRefundId);
            }

            $customerRefundJournalId = $input["customer_refund_journal_id"];
        }

        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_TRANSACTION_CREATE_REQUEST,
            [
                'input'          => $input,
                'customerRefund' => $customerRefund,
            ]
        );

        try
        {
            $response = $this->createTransferReversalTransactionsInReverseShadow($reversals, $customerRefundJournalId, $customerRefund, $isRearchRefund);

            $this->trace->info(TraceCode::PG_LEDGER_CREATE_TRANSACTION_SUCCESS,
                [
                    LedgerConstants::RESPONSE => $response,
                    LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
                    LedgerOutboxConstants::SOURCE     => LedgerOutboxConstants::SYNC
                ]
            );

            $this->trace->count(Metrics::PG_LEDGER_CREATE_TRANSACTION_SUCCESS, [
                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
                LedgerOutboxConstants::SOURCE     => LedgerOutboxConstants::SYNC
            ]);

            return $response;
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metrics::PG_LEDGER_CREATE_TRANSACTION_FAILURE, [
                [
                    LedgerConstants::TRANSACTOR_EVENT   =>  LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
                    LedgerOutboxConstants::SOURCE       => LedgerOutboxConstants::SYNC
                ]
            ]);

            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::TRANSFER_REVERSAL_TRANSACTION_CREATE_FAILURE,
                ['input'         => $input]
            );

            throw $ex;
        }

    }

    private function createTransferReversalTransactionsInReverseShadow(array $reversals, $customerRefundJournalId, RefundEntity $customerRefund = null, $isRearchRefund = false)
    {

        return $this->repo->transaction(function() use ($reversals, $customerRefund, $customerRefundJournalId, $isRearchRefund)
        {
            $txnCore = (new Transaction\Core);

            $reversalTransactionsList = [];

            foreach ($reversals as $item) {

                $reversalId             = $item["transfer_reversal_id"];
                $reversalJournalId      = $item["transfer_reversal_journal_id"];
                $refundId               = $item["refund_id"];
                $dummyRefundJournalId   = $item["refund_journal_id"];

                $reversal = $this->repo->reversal->findOrFail($reversalId);

                $resource = $reversal->getId() . '_transaction';

                $reversalAndRefundTxnIds = $this->mutex->acquireAndRelease(
                    $resource,
                    function () use ($reversal, $reversalId, $reversalJournalId, $refundId, $dummyRefundJournalId, $reversalTransactionsList, $isRearchRefund, $txnCore) {

                                // avoid duplicate txn creation
                        $reversalTxn = $this->repo->transaction->findByEntityId($reversal->getId(), $reversal->merchant);

                        if (isset($reversalTxn) === false) {
                            // Create the transaction for reversal
                            $reversalTxn = $txnCore->createFromTransferReversal($reversal, $reversalJournalId);

                            $this->repo->saveOrFail($reversalTxn);

                            $reversal->transaction()->associate($reversalTxn);

                            $this->repo->saveOrFail($reversal);
                        }
                        else
                        {
                            $this->app['trace']->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_EXISTS, [
                                'entity'   => 'reversal',
                                'txn'      => $reversalTxn
                            ]);
                        }

                        if($isRearchRefund)
                        {
                            $refund = (new Refund\Repository())->fetchExternalRefundById($refundId, '', [], true);
                        }
                        else
                        {
                            $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
                                'method'       => 'createTransferReversalTransactionsInReverseShadow',
                            ]);
                            $refund = $this->repo->refund->findOrFail($refundId);
                        }

                        $transferPayment = $this->repo->payment->findOrFail($refund->getPaymentId());

                        $refund->payment()->associate($transferPayment);

                        // Create the transaction for dummy refund
                        $dummyRefundInput = [
                            Refund\Constants::JOURNAL_ID            => $dummyRefundJournalId,
                            Refund\Constants::ID                    => $refund->getId(),
                            Refund\Constants::PAYMENT_ID            => $refund->payment->getId(),
                            Refund\Constants::AMOUNT                => $refund->getAmount(),
                            Refund\Constants::SCROOGE_BASE_AMOUNT   => $refund->getBaseAmount(),
                            Refund\Constants::SPEED_DECISIONED      => $refund->getSpeedDecisioned(),
                            Refund\Constants::SCROOGE_GATEWAY       => $refund->getGateway(),
                            Refund\Constants::MODE                  => $this->mode,
                            Refund\Constants::FEE                   => $refund->getFee(),
                            Refund\Constants::TAX                   => $refund->getTax(),
                        ];

                        $paymentProcessor = new Payment\Processor\Processor($refund->merchant);

                        // avoid duplicate txn creation
                        $refundTxn = $this->repo->transaction->findByEntityIdWithoutMerchant($refund->getId());

                        $refundTxnId = null;

                        if(isset($refundTxn) === false)
                        {
                            $dummyRefundTxnResponse = $paymentProcessor->scroogeRefundTransactionCreate($refund->payment, $dummyRefundInput);

                            $refundTxnId = $dummyRefundTxnResponse['data'][Refund\Constants::TRANSACTION_ID];

                            // associate refund with txn
                            $txn = $this->repo->transaction->findByEntityIdWithoutMerchant($refundId);

                            if ((isset($txn) === true) and ($isRearchRefund === false)) {
                                $refund->transaction()->associate($txn);
                                $refund->exists = true;
                                $this->repo->saveOrFail($refund);
                            }
                        }
                        else
                        {
                            $refundTxnId = $refundTxn->getId();

                            $this->app['trace']->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_EXISTS, [
                                'entity'   => 'refund',
                                'txn'      => $refundTxn
                            ]);
                        }

                        return [
                            "reversal_txn_id"     => $reversalTxn->getId(),
                            "refund_txn_id"       => $refundTxnId,
                        ];
                    },
                    60,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                    5,
                    100,
                    200
                );

                $reversalTransactionsList[] = $reversalAndRefundTxnIds;

            }

            // Create the transaction for customer refund if applicable
            $customerRefundTxnResponse = [];

            $customerRefundTxnId = "";

            if($customerRefund !== null)
            {
                $customerRefundInput = [
                    Refund\Constants::JOURNAL_ID            => $customerRefundJournalId,
                    Refund\Constants::ID                    => $customerRefund->getId(),
                    Refund\Constants::PAYMENT_ID            => $customerRefund->payment->getId(),
                    Refund\Constants::AMOUNT                => $customerRefund->getAmount(),
                    Refund\Constants::SCROOGE_BASE_AMOUNT   => $customerRefund->getBaseAmount(),
                    Refund\Constants::SPEED_DECISIONED      => $customerRefund->getSpeedDecisioned(),
                    Refund\Constants::SCROOGE_GATEWAY       => $customerRefund->getGateway(),
                    Refund\Constants::MODE                  => $this->mode,
                    Refund\Constants::FEE                   => $customerRefund->getFee(),
                    Refund\Constants::TAX                   => $customerRefund->getTax(),
                ];

                $sourcePayment = $customerRefund->payment;

                $paymentProcessor = new Payment\Processor\Processor($customerRefund->merchant);

                // avoid duplicate txn creation
                $txn = $this->repo->transaction->findByEntityIdWithoutMerchant($customerRefund->getId());

                if(isset($txn) === false)
                {
                    $customerRefundTxnResponse = $paymentProcessor->scroogeRefundTransactionCreate($sourcePayment, $customerRefundInput);

                    $customerRefundTxnId = $customerRefundTxnResponse['data'][Refund\Constants::TRANSACTION_ID];

                    // associate customer refund with txn
                    $txn = $this->repo->transaction->findByEntityIdWithoutMerchant($customerRefund->getId());

                    if ((isset($txn) === true) and ($isRearchRefund === false)) {
                        $customerRefund->transaction()->associate($txn);
                        $customerRefund->exists = true;
                        $this->repo->saveOrFail($customerRefund);
                    }
                }
                else
                {
                    $customerRefundTxnId = $txn->getId();

                    $this->app['trace']->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_EXISTS, [
                        'entity'   => 'customer refund',
                        'txn'      => $txn
                    ]);
                }
            }

            return [
                "reversal_and_refund_txn_ids" => $reversalTransactionsList,
                "customer_refund_txn_id"      => $customerRefundTxnId,
            ];

        });

    }

    private function createLedgerEntriesForTransferReverseShadowInSync(Entity $transfer, Payment\Entity $transferPayment)
    {
        if (isset($transferPayment) === false)
        {
            return;
        }

        $transferMerchant = $this->repo->merchant->findOrFail($transfer->merchant->getId());

        $paymentMerchant = $this->repo->merchant->findOrFail($transferPayment->merchant->getId());

        $reverseShadowEnabledForTransferDebitMid = $transferMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);
        $reverseShadowEnabledForLinkedAccount = $paymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if (($reverseShadowEnabledForTransferDebitMid === false) and ($reverseShadowEnabledForLinkedAccount === false))
        {
            return;
        }

        if (( $reverseShadowEnabledForTransferDebitMid === false) ^ ( $reverseShadowEnabledForLinkedAccount === false))
        {
            $this->trace->info(
                TraceCode::PG_LEDGER_TRANSFER_MERCHANTS_ONBOARDING_MISMATCH,
                [
                    'transfer_id'                                   => $transfer->getId(),
                    'parent_merchant_id'                            => $transferMerchant->getId(),
                    'reverse_shadow_enabled_for_transfer_debit_mid' => $reverseShadowEnabledForTransferDebitMid,
                    'linked_account_merchant_id'                    => $paymentMerchant->getId(),
                    'reverse_shadow_enabled_for_linked_account'     => $reverseShadowEnabledForLinkedAccount,
                ]
            );

            if (( $reverseShadowEnabledForTransferDebitMid === true) and ( $reverseShadowEnabledForLinkedAccount === false))
            {
                //onboard linked account mid if transfer debit mid on reverse shadow
                $input = [
                    "merchant_ids" => [$paymentMerchant->getId()],
                ];

                try
                {
                    (new Feature\Service)->onboardMerchantOnPGReverseShadow($input, true);
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        null,
                        TraceCode::PG_LEDGER_TRANSFER_MERCHANTS_AUTO_ONBOARD_FAILURE,
                        [
                            'transfer_id'                                   => $transfer->getId(),
                            'parent_merchant_id'                            => $transfer->merchant->getId(),
                            'reverse_shadow_enabled_for_transfer_debit_mid' => $reverseShadowEnabledForTransferDebitMid,
                            'linked_account_merchant_id'                    => $paymentMerchant->getId(),
                            'reverse_shadow_enabled_for_linked_account'     => $reverseShadowEnabledForLinkedAccount,
                        ]
                    );

                    (new Metric())->pushTransferMerchantsOnboardingMismatchMetrics($reverseShadowEnabledForTransferDebitMid,$reverseShadowEnabledForLinkedAccount);

                    return;
                }
            }
            else
            {
                (new Metric())->pushTransferMerchantsOnboardingMismatchMetrics($reverseShadowEnabledForTransferDebitMid,$reverseShadowEnabledForLinkedAccount);

                return;
            }
        }

        [$fee, $tax] = (new ReverseShadowTransfersCore())->createLedgerEntriesForTransferReverseShadowInSync($transfer, $transferPayment);

        $this->trace->info(TraceCode::DIRECT_TRANSFER_LEDGER_ENTRIES_SUCCESS, [
            LedgerConstants::TRANSFER_ID => $transfer->getId(),
            LedgerConstants::PAYMENT_ID => $transferPayment->getId(),
            LedgerConstants::MERCHANT_ID => $transfer->getMerchantId(),
        ]);

        return $transfer;
    }

    public function isAsyncCustomerTransferExperimentEnabledForMerchant($merchantId, $experimentId, $mode): bool
    {
        $this->trace->info(TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER, [
            'splitz_input_experiment_id' => $experimentId,
            'splitz_input_merchant_id'   => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
            'mode' => $mode
        ]);

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $this->trace->info(TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER, [
            'splitz_output' => $variant,
        ]);

        return $variant === self::FLAG_ASYNC_CUSTOMER_TRANSFER;
    }

    public static function getTransferProcessingMutexResource($transferType, $payment)
    {
        $isExperimentEnabled = (new Core())->checkIfPaymentIdMutexExperimentIsEnabled($payment->merchant);

        if ($isExperimentEnabled === true)
        {
            return $payment->getId();
        }

        if ($transferType == Transfer\Constant::ORDER) {
            return 'order_transfer_process_' . $payment->getPublicId();
        } else if ($transferType == Transfer\Constant::PAYMENT) {
            return 'payment_transfer_process_' . $payment->getPublicId();
        } else {
            throw new Exception\LogicException('Unsupported transfer type');
        }
    }

    protected function checkIfPaymentIdMutexExperimentIsEnabled($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            self::PAYMENT_ID_MUTEX_FOR_TRF_PROCESSING_EXPERIMENT,
            $this->mode
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(
            TraceCode::PAYMENT_ID_MUTEX_TRANSFER_PROCESSING_EXP_CHECK,
            [
                'merchant_id'    => $merchant->getId(),
                'is_exp_enabled' => $isExperimentEnabled,
            ]);

        return $isExperimentEnabled;
    }

    public function pushTransferForAsyncBalanceUpdateIfApplicable($transfer): void
    {
        // TODO: Remove this check once Airtel is onboarded to reverse shadow
        $asyncBalanceDebitMids = array_merge(Constant::MIDS_FOR_ASYNC_BALANCE_UPDATE_FOR_TRANSFER_DEBIT_TXNS,
            Constant::MIDS_FOR_ASYNC_BALANCE_UPDATE_FOR_TRANSFER_DEBIT_TXNS_WITH_FEE);

        if (($transfer->isProcessed() === true) and
            (in_array($transfer->merchant->getId(), $asyncBalanceDebitMids) === true))
        {
            $this->dispatchForAsyncBalanceUpdate($transfer);
        }
    }

    public function checkIfFailCreatedAndPendingTransfersExperimentIsEnabled($merchant)
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::FAIL_CREATED_AND_PENDING_TRANSFERS_IF_PAYMENT_REFUNDED,
            $this->mode
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(
            TraceCode::FAIL_CREATED_AND_PENDING_TRANSFERS_EXPERIMENT_CHECK,
            [
                'merchant_id'    => $merchant->getId(),
                'is_exp_enabled' => $isExperimentEnabled,
            ]);

        return $isExperimentEnabled;
    }

    public function failTransferIfSourcePaymentIsRefunded($transfer, $payment)
    {
        $transfer->setFailed();

        $sourceType = $transfer->getSourceType();

        if ($sourceType === Constant::PAYMENT)
        {
            $transfer->setAttempts(Transfer\Constant::MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS);
        }
        else if ($sourceType === Constant::ORDER)
        {
            $transfer->setAttempts(Transfer\Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS);
        }

        $transfer->setErrorCode(ErrorCode::BAD_REQUEST_TRANSFER_FAILED_AS_SOURCE_PAYMENT_REFUNDED);

        $transfer->setMessage(PublicErrorDescription::BAD_REQUEST_TRANSFER_FAILED_AS_SOURCE_PAYMENT_REFUNDED);

        $transfer->saveOrFail();

        $this->trace->info(
            TraceCode::TRANSFER_FAILED_AS_SOURCE_PAYMENT_IS_REFUNDED,
            [
                'transfer_id'      => $transfer->getId(),
                'source_type'      => $sourceType,
                'source_id'        => $transfer->getSourceId(),
                'payment_id'       => $payment->getId(),
            ]);
    }

    public function isTransferOnHoldFlagEnabled($merchant)
    {
        $merchant = $this->repo->merchant->findOrFail($merchant->getId());

        if ($merchant->isFeatureEnabled(Feature\Constants::TRANSFER_ON_HOLD) === true)
        {
            return true;
        }

        return false;
    }

    public function fetchPaymentEntityFromOrder(Order\Entity $order)
    {
        $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($order->getId(), $order->getMerchantId());

        $rearchPayments = $this->app['pg_router']->fetchOrderPayments($order->getId(), $order->getMerchantId());

        $allPayments = $apiPayments->merge($rearchPayments);

        $payment = null;

        foreach ($allPayments as $singlePayment)
        {
            if (($singlePayment->getStatus() === Payment\Status::CAPTURED))
            {
                $payment = $singlePayment;

                break;
            }
        }
        return $payment;
    }

    public function dispatchForAsyncBalanceUpdate($transfer)
    {
        try
        {
            $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::ROUTE_ASYNC_BALANCE_UPDATE_QUEUE_CONFIG]) ?? [];
        }
        catch (\Throwable $ex)
        {
            // Fallback to default queue if an error occurs
            $config = [];
        }

        if (in_array($transfer->merchant->getId(), $config[Constant::DEDICATED_QUEUE_ONE] ?? []) === true)
        {
            AsyncBalanceUpdateForTransferQueueOne::dispatch($this->mode, $transfer->getId())->delay(10 * 60);
        }
        else if (in_array($transfer->merchant->getId(), $config[Constant::DEDICATED_QUEUE_TWO] ?? []) === true)
        {
            AsyncBalanceUpdateForTransferQueueTwo::dispatch($this->mode, $transfer->getId())->delay(10 * 60);
        }
        else if (in_array($transfer->merchant->getId(), $config[Constant::DEDICATED_QUEUE_THREE] ?? []) === true)
        {
            AsyncBalanceUpdateForTransferQueueThree::dispatch($this->mode, $transfer->getId())->delay(10 * 60);
        }
        else
        {
            AsyncBalanceUpdateForTransfer::dispatch($this->mode, $transfer->getId())->delay(10 * 60);
        }

        $this->trace->info(
            TraceCode::ASYNC_BALANCE_UPDATE_TXN_DISPATCHED,
            [
                'transfer_id'         => $transfer->getId(),
                'merchant_id'         => $transfer->getMerchantId(),
            ]);
    }

    public function isNssStreamingViaRouteMicroserviceEnabled($merchantId, $mode): bool
    {
        $experimentId = $this->app['config']->get('app.route_linked_account_2fa_exp_id');

        $properties = [
            'id' => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $experimentEnabled = false;

        if ($variant === 'variant_on') {
            $experimentEnabled = true;
        }

        $this->trace->info(TraceCode::ROUTE_MICROSERVICE_NSS_STREAMING_EXP_CHECK, [
            'merchant_id' => $merchantId,
            'experiment_id' => $experimentId,
            'mode' => $mode,
            'enabled' => $experimentEnabled,
        ]);

        return $experimentEnabled;
    }
}
