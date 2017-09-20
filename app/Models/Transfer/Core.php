<?php

namespace RZP\Models\Transfer;

use RZP\Constants;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Customer;
use RZP\Models\Payment;
use RZP\Models\Feature;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a direct transfer from Merchant balance
     *
     * @param  array                    $input
     * @param  Merchant\Entity          $merchant
     * @return Transfer\Entity
     */
    public function createForMerchant(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(
            TraceCode::TRANSFER_CREATE_REQUEST,
            ['input' => $input]);

        $this->validateMerchantForTransfer($merchant);

        return $this->repo->transaction(function () use ($input, $merchant)
        {
            $transfer = $this->makeTransfer($input, $merchant, $merchant);

            $this->trace->info(
                TraceCode::TRANSFER_CREATE_SUCCESS,
                ['transfer_id' => $transfer->getId()]);

            return $transfer;
        });
    }

    /**
     * Create a transfer from a captured payment source
     *
     * @param   Payment\Entity          $payment
     * @param   array                   $input
     * @param   Merchant\Entity         $merchant
     * @return  Base\PublicCollection
     */
    public function createForPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant)
    {
        $transfers = new Base\PublicCollection;

        $this->validateMerchantForTransfer($merchant);

        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

        (new Validator)->validateTransfers($payment, $merchantBalance, $input);

        $totalTransferAmount = 0;

        foreach ($input as $transfer)
        {
            $transfer = $this->makeTransfer($transfer, $payment, $merchant);

            $totalTransferAmount += $transfer['amount'];

            $transfers->push($transfer);
        }

        $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

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

        return $this->repo->transaction(function () use ($transfer, $input)
        {
            $this->updatePaymentHold($transfer);

            $this->repo->saveOrFail($transfer);

            $this->trace->info(
                TraceCode::TRANSFER_EDIT_SUCCESS,
                ['transfer_id' => $transfer->getId()]);

            return $transfer;
        });
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
        $transfer = new Entity;

        $transfer->generateId();

        $transfer->build($input);

        $transfer->merchant()->associate($merchant);

        $transfer->source()->associate($source);

        $transfer->to()->associate($to);

        // Create a transaction for the transfer; debits the source merchant
        $txn = (new Transaction\Core)->createFromTransfer($transfer);

        $transfer->setFees($txn->getFee());

        $transfer->setServiceTax($txn->getServiceTax());

        $transfer->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($transfer);

        return $transfer;
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

        $txn = (new Transaction\Core)->updateOnHoldToggle($payment);

        $this->repo->saveOrFail($payment);

        $this->repo->saveOrFail($txn);
    }

    /**
     * Called on payment transfer operation
     * Updates the value of amount_transferred in Payments
     *
     * @param  Payment\Entity $payment
     * @param  int            $amount
     */
    protected function updatePaymentAmountTransferred(Payment\Entity $payment, int $amount)
    {
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
     * @param  array                $input
     * @param  Base\Entity          $source
     * @param  Merchant\Entity      $merchant
     * @return Transfer\Entity
     */
    protected function makeTransfer(array $input, Base\Entity $source, Merchant\Entity $merchant) : Entity
    {
        $validator = new Validator;

        $validator->validateInput('create', $input);

        if (isset($input[ToType::CUSTOMER]) === true)
        {
            $id = $input[ToType::CUSTOMER];

            return $this->customerTransfer($id, $source, $input, $merchant);
        }
        else if (isset($input[ToType::ACCOUNT]) === true)
        {
            $id = $input[ToType::ACCOUNT];

            return $this->accountTransfer($id, $source, $input, $merchant);
        }
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

        $txn = $transfer->transaction;

        // Fetch customer balance and credit
        $balance = (new Customer\Balance\Core)->fetchOrCreate($to, $merchant);

        (new Customer\Balance\Core)->credit($balance, $txn->getAmount());

        $customerTxn = (new Customer\Transaction\Core)
                            ->createForCustomerCredit($transfer, $input['amount'], $to->getId(), $merchant);

        $this->repo->saveOrFail($customerTxn);

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
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function accountTransfer(string $accountId, Base\Entity $source, array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT,
            ['transfer' => $input]);

        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE, $merchant);

        $originPayment = null;

        $to = $this->repo
                   ->merchant
                   ->fetchByAccountIdAndMerchant($accountId, $merchant);

        if (($source instanceof Payment\Entity) === true)
        {
            $originPayment = $source;
        }

        $merchant->getValidator()->validateMerchantForMarketplaceTransfer($to, $this->mode);

        $transfer = $this->createTransfer($source, $to, $input, $merchant);

        $transferPayment = (new Payment\Processor\Processor($to))->processTransfer($input, $originPayment);

        $transferPayment->transfer()->associate($transfer);

        $this->repo->saveOrFail($transferPayment);

        return $transfer;
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
}
