<?php

namespace RZP\Models\Transfer;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\Tracer;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Jobs\TransferProcess;
use RZP\Models\Settlement\Bucket;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\TransferProcessSlice;
use RZP\Jobs\TransferProcessCapitalFloat;
use RZP\Jobs\TransferProcessKeyMerchants;

class Core extends Base\Core
{
    protected $mutex;

    protected $razorx;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->razorx = $this->app['razorx'];
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

    /**
     * Create a direct transfer from Merchant balance
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     *
     * @return Transfer\Entity
     * @throws Exception\BadRequestException
     */
    public function createForMerchant(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(
            TraceCode::TRANSFER_CREATE_REQUEST,
            ['input' => $input]);

        if (isset($input[ToType::ACCOUNT]) === true)
        {
            $this->checkForDirectTransferFeature($merchant);
        }

        $this->validateMerchantForTransfer($merchant);

        $validator = new Validator;

        $validator->validateToType($input);

        $inputArray = array($input);

        $this->addAccountFromAccountCodeIfApplicable($inputArray);

        $input = $inputArray[0];

        $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($input, $merchant);

        $validator->validateInput('create', $input);

        $validator->validateTransferMaxAmount($input[Entity::AMOUNT], $merchant);

        $transfer = null;

        try {
            $transfer = $this->makeTransferTransaction($input, $merchant, $validator);
        }
        catch (\Throwable $ex)
        {
            // Checks if the exception is caused by db connection loss and reconnects to DB(one retry)
            // made this change as a fix for production issue SI-4668
            $causedByLostConnection = $this->app['db.connector.mysql']->checkAndReloadDBIfCausedByLostConnection($ex);

            if($causedByLostConnection === true)
            {
                $transfer = $this->makeTransferTransaction($input, $merchant, $validator);
            }
            else
            {
                $this->trace->traceException($ex, null, TraceCode::DIRECT_TRANSFER_CREATE_EXPECTION,[]);

                throw $ex;
            }
        }
        if ($transfer->isProcessed() === true)
        {
            $this->eventTransferProcessed($transfer);
        }

        return $transfer;
    }

    /**
     * Create a transfer from a captured payment source
     *
     * @param   Payment\Entity  $payment
     * @param   array           $input
     * @param   Merchant\Entity $merchant
     *
     * @return  Base\PublicCollection
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createForPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant, bool $asyncTransfer)
    {
        $this->validateMerchantForTransfer($merchant);

        $this->addAccountFromAccountCodeIfApplicable($input);

        $orderTransfers = [];

        if ($payment->hasOrder() === true)
        {
            $orderTransfers = $this->repo->transfer->fetchBySourceTypeAndIdAndMerchant(Constants\Entity::ORDER, $payment->getApiOrderId(), $this->merchant);
        }

        foreach ($input as $transfer)
        {
            $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($transfer, $merchant);
        }

        (new Validator)->validateTransfers($payment, $input, $orderTransfers);

        $totalTransferAmount = 0;

        $transfers = new Base\PublicCollection;

        foreach ($input as $transfer)
        {
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

    public function createForOrder(Order\Entity $order, array $transferInput)
    {
        $transfers = new Base\Collection();

        foreach ($transferInput as $input)
        {
            $input[Entity::STATUS] = Status::CREATED;

            $input[Entity::ORIGIN] = Origin::ORDER_AUTOMATION;

            $this->validateLinkedAccountActivationStatusAndBankVerificationStatus($input, $this->merchant);

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
            else if (isset($input[ToType::ACCOUNT]) === true) {

                $to = $this->repo
                    ->account
                    ->findByPublicIdAndMerchant($input[ToType::ACCOUNT], $this->merchant);

                // extracts linked account notes and validates.
                $this->getLinkedAccountNotes($input);
            }
            $transfer = Tracer::inSpan(['name' => 'order.transfer.create.build'], function() use ($order, $to, $input)
            {
                return $this->buildTransferEntity($order, $to, $input, $this->merchant);
            });

            $this->repo->transfer->saveOrFail($transfer);

            $transfers->push($transfer->toArrayPublic());
        }

        return $transfers;
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

        $this->dispatchForSettlementService($payment);

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

        return $this->createTransactionForTransfer($transfer);
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

        $payment->setOnHold($transferOnHold);

        $payment->setOnHoldUntil($transferOnHoldUntil);

        $txnCore = new Transaction\Core;

        $txn = $txnCore->updateOnHoldToggle($payment);

        $this->repo->saveOrFail($payment);

        $this->repo->saveOrFail($txn);

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
    protected function updatePaymentAmountTransferred(Payment\Entity $payment, int $amount)
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

        $transfer = null;

        if (isset($input[ToType::CUSTOMER]) === true)
        {
            $id = $input[ToType::CUSTOMER];

            $asyncTransfer = false;

            return $this->customerTransfer($id, $source, $input, $merchant);
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
        Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER,
            ['transfer' => $input]);

        $this->verifyFeatureAllowed(Feature\Constants::OPENWALLET, $merchant);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($customerId, $merchant);

        // Create a transfer its corresponding txn - debits the merchant
        $transfer = $this->createTransfer($source, $to, $input, $merchant);

        // Create customer balance if it doesn't exist.
        (new Customer\Balance\Core)->fetchOrCreate($to, $merchant);

        $txn = $transfer->transaction;

        (new Customer\Transaction\Core)->createForCustomerCredit($transfer,
                                                                 $txn->getAmount(),
                                                                 $to->getId(),
                                                                 $merchant);

        return $transfer;
    }

    /**
     * Transfer to a Marketplace account
     *
     * @param string           $accountId
     * @param  Base\Entity     $source
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     *
     * @return Entity
     */
    protected function accountTransfer(string $accountId, Base\Entity $source, array $input, Merchant\Entity $merchant, bool $aysncTransfer)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT,
            ['transfer' => $input]);

        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE, $merchant);

        $to = $this->repo
                   ->account
                   ->findByPublicIdAndMerchant($accountId, $merchant);

        $originPayment = null;

        if (($source instanceof Payment\Entity) === true)
        {
            $originPayment = $source;
        }

        $merchant->getValidator()->validateMerchantForMarketplaceTransfer($to, $this->mode);


        if($aysncTransfer === true)
        {
            $transfer = Tracer::inSpan(['name' => 'payment.transfer.create.make_transfer.account_transfer.build'], function() use ($source, $to, $input, $merchant)
            {
                return $this->buildTransferEntity($source, $to, $input, $merchant);
            });

            $transfer->setStatus(Status::PENDING);

            $this->repo->saveOrFail($transfer);

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

    public function validateTransfersInput(int $orderAmount, array $transfers, $merchant)
    {
        if ($this->merchant === null)
        {
            $this->merchant = $merchant;
        }

        $this->validateMerchantForTransfer($this->merchant);

        $validator = new Validator();

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
            $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE, $this->merchant);

            foreach ($transfers as $transfer)
            {
                $to = $this->repo
                    ->account
                    ->findByPublicIdAndMerchant($transfer[ToType::ACCOUNT], $this->merchant);

                $this->merchant->getValidator()->validateMerchantForMarketplaceTransfer($to, $this->mode);
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

    public function createTransactionForTransfer($transfer)
    {
        $txnCore = new Transaction\Core;

        // Create a transaction for the transfer; debits the source merchant
        list($txn,$feesSplit) = $txnCore->createFromTransfer($transfer);

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

        $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $originPayment);

        $transfer->setProcessed();

        $this->repo->saveOrFail($transfer);

        $transferPayment->transfer()->associate($transfer);

        $this->repo->saveOrFail($transferPayment);

        return $transfer;
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

    public function updateSettlementStatusInTransfers(string $settlementId)
    {
        $transferIds = $this->repo->transfer->getIdsByRecipientSettlementId($settlementId, Status::$forSettlementStatusUpdate);

        $this->traceTransferIdsFetchedForSettlementStatusUpdate($settlementId, $transferIds);

        foreach ($transferIds as $transferId)
        {
            $transfer = $this->repo->transfer->find($transferId);

            $transfer->setSettlementStatus(SettlementStatus::SETTLED);

            $transfer->saveOrFail();
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFERS_SUCCESS,
            [
                'settlement_id' => $settlementId,
                'count'         => count($transferIds),
            ]
        );
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

            (new Metric())->pushTransferProcessingTimeMetrics($sourceType, $processingTime);
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

    public function dispatchForTransferProcessing(string $sourceType, Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        if (($merchant->isCapitalFloatRouteMerchant() === true) and
            ($this->isLiveMode() === true))
        {
            TransferProcessCapitalFloat::dispatch($this->mode, $payment->getId(), $sourceType);

            return;
        }
        else if (($merchant->isSliceRouteMerchant() === true) and
            ($this->isLiveMode() === true))
        {
            TransferProcessSlice::dispatch($this->mode, $payment->getId(), $sourceType);

            return;
        }
        else if ($merchant->isRouteKeyMerchant() === true)
        {
            TransferProcessKeyMerchants::dispatch($this->mode, $payment->getId(), $sourceType);

            return;
        }

        TransferProcess::dispatch($this->mode, $payment->getId(), $sourceType);
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
}
