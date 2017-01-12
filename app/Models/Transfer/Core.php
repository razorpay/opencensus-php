<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Constants\Entity as E;
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
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a direct transfer from Merchant balance
     *
     * @param  array                    $input
     * @return Transfer\Entity
     */
    public function createForMerchant(array $input) : Entity
    {
        return $this->repo->transaction(function () use ($input)
        {
            $transfer = $this->makeTransfer($input, $this->merchant);

            return $transfer;
        });
    }

    /**
     * Create a transfer from a source payment
     *
     * @param   Payment\Entity          $payment
     * @param   array                   $input
     * @return  Base\PublicCollection
     */
    public function createForPayment(Payment\Entity $payment, array $input)
    {
        $transfers = new Base\PublicCollection;

        $merchantBalance = $this->repo->balance->getMerchantBalance($this->merchant);

        (new Validator)->validateTransfers($payment, $merchantBalance, $input);

        $totalTransferAmount = 0;

        foreach ($input as $transfer)
        {
            $transfer = $this->makeTransfer($transfer, $payment);

            $totalTransferAmount += $transfer['amount'];

            $transfers->push($transfer);
        }

        $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

        return $transfers;
    }

    /**
     * Edit the attributes of a transfer entity
     * Currently allowed for on_hold and hold_until fields
     *
     * @param  string           $id
     * @param  array            $input
     * @return Transfer\Entity
     */
    public function edit(string $id, array $input) : Entity
    {
        $transfer = $this->repo->transfer->findByPublicIdAndMerchant($id, $this->merchant);

        $transfer->edit($input);

        if ($transfer->getOnHold() === false)
        {
            $transfer->setHoldUntil(null);
        }

        return $this->repo->transaction(function () use ($transfer, $input)
        {
            $this->repo->saveOrFail($transfer);

            $this->updatePaymentHold($transfer, $input);

            return $transfer;
        });
    }

    /**
     * Creates and saves a new transfer entity
     *
     * @param  Base\Entity        $source      Source entity for transfer
     * @param  Base\Entity        $to          Receiving entity for transfer
     * @param  int                $amount
     * @return Transfer\Entity
     */
    protected function createTransfer(Base\Entity $source, Base\Entity $to, array $input) : Entity
    {
        $transferData = [
            Entity::TO_ID           => $to->getId(),
            Entity::TO_TYPE         => $to->getEntityName(),
            Entity::SOURCE_ID       => $source->getId(),
            Entity::SOURCE_TYPE     => $source->getEntityName(),
        ];

        $transferData = $transferData + $input;

        $transfer = new Entity;

        $transfer->generate($transferData);

        $transfer->fill($transferData);

        $transfer->generateId();

        $transfer->merchant()->associate($this->merchant);

        $txn = (new Transaction\Core)->createFromTransfer($transfer, $to);

        $this->repo->saveOrFail($txn);

        $transfer->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        return $transfer;
    }

    protected function updatePaymentHold(Entity $transfer, array $input)
    {
        $payment = $this->repo
                        ->payment
                        ->findByTransferIdAndMerchant(
                            $transfer->getId(),
                            $transfer->getToId());

        $payment->setOnHold($transfer->getOnHold());

        $payment->setHoldUntil($transfer->getHoldUntil());

        $txn = (new Transaction\Core)->updateOnHoldToggle($payment);

        $this->repo->saveOrFail($payment);

        $this->repo->saveOrFail($txn);
    }

    protected function updatePaymentAmountTransferred($payment, int $amount)
    {
        $this->mutex->acquireAndRelease($payment->getId(), function() use ($payment, $amount)
        {
            $payment->transferAmount($amount);

            $this->repo->saveOrFail($payment);
        });
    }

    /**
     * Create and process a transfer
     *
     * @param  array                $input
     * @param  Base\Entity          $source
     * @return Transfer\Entity
     */
    protected function makeTransfer(array $input, Base\Entity $source) : Entity
    {
        $validator = new Validator;

        $validator->validateInput('transfer', $input);

        $validator->validateHoldParameters($input);

        if (isset($input[ToType::CUSTOMER]) === true)
        {
            $id = $input[ToType::CUSTOMER];

            return $this->customerTransfer($id, $source, $input);
        }
        else if (isset($input[ToType::ACCOUNT]) === true)
        {
            $id = $input[ToType::ACCOUNT];

            return $this->accountTransfer($id, $source, $input);
        }
    }

    /**
     * Transfer to a customer account
     *
     * @param  Base\Entity          $source
     * @param  array                $input
     * @return Transfer\Entity
     */
    protected function customerTransfer(string $customerId, Base\Entity $source, array $input) : Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::OPENWALLET);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER,
            ['transfer' => $input]);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($customerId, $this->merchant);

        $transfer = $this->createTransfer($source, $to, $input);

        $customerTxn = (new Customer\Transaction\Core)
                            ->createFromCustomerCredit($transfer, $input['amount'], $to->getId());

        $this->repo->saveOrFail($customerTxn);

        return $transfer;
    }

    /**
     * Transfer to a Marketplace account
     *
     * @param  Base\Entity      $source
     * @param  array            $input
     * @return Transfer\Entity
     */
    protected function accountTransfer(string $accountId, Base\Entity $source, array $input) : Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT,
            ['transfer' => $input]);

        $originPayment = null;

        $to = $this->repo
                   ->merchant
                   ->fetchAccountByIdAndMerchant($accountId, $this->merchant);

        if (($source instanceof Payment\Entity) === true)
        {
            $originPayment = $source;

            $this->checkMultipleMarketplaceTransfer($originPayment->getId(), $accountId);

            $input['contact'] = $originPayment->getContact();

            $input['email']   = $originPayment->getEmail();
        }

        $transfer = $this->createTransfer($source, $to, $input);

        $transferPayment = (new Payment\Service)->processTransfer($to, $input, $originPayment);

        $transferPayment->transfer()->associate($transfer);

        $this->repo->saveOrFail($transferPayment);

        return $transfer;
    }

    protected function verifyFeatureAllowed(string $feature)
    {
        if ($this->merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    "$feature is not supported");
        }
    }

    /**
     * Check that: A transfer can only be done once to an account
     * for a payment
     *
     * @param  string           $paymentId
     * @param  string           $accountId
     * @throws Exception\BadRequestException
     */
    protected function checkMultipleMarketplaceTransfer(string $paymentId, string $accountId)
    {
        Merchant\AccountEntity::verifyIdAndStripSign($accountId);

        $transfers = $this->repo
                          ->transfer
                          ->fetchBySourcePaymentToAccountAndMerchant(
                            $paymentId, $accountId, $this->merchant);

        if (count($transfers) !== 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_MULTIPLE_TRANSFERS_TO_SAME_ACCOUNT);
        }
    }
}
