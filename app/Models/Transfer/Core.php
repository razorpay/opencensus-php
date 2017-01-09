<?php

namespace RZP\Models\Transfer;

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
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Creates and saves a new transfer entity
     *
     * @param  Base\Entity        $from        Source entity for transfer
     * @param  Base\Entity        $to          Recieving entity for transfer
     * @param  int                $amount
     * @return Transfer\Entity
     */
    public function createTransfer(Base\Entity $to, Base\Entity $source, array $input, int $baseAmount) : Entity
    {
        $transferData = [
            Entity::TO_ID           => $to->getId(),
            Entity::TO_TYPE         => $to->getEntityName(),
            Entity::SOURCE_ID       => $source->getId(),
            Entity::SOURCE_TYPE     => $source->getEntityName(),
            Entity::AMOUNT          => $input['amount'],
            Entity::CURRENCY        => $input['currency'],
        ];

        $transfer = (new Entity);

        $transfer->generate($transferData);

        $transfer->fill($transferData);

        $transfer->generateId();

        $transfer->setBaseAmount($baseAmount);

        $transfer->merchant()->associate($this->merchant);

        $txn = (new Transaction\Core)->createFromTransfer($transfer, $to);

        $this->repo->saveOrFail($txn);

        $transfer->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        return $transfer;
    }

    /**
     * Create a direct transfer from Merchant balance
     *
     * @param  array  $input
     * @return Transfer\Entity
     */
    public function createForMerchant(array $input) : Entity
    {
        return $this->repo->transaction(function () use ($input)
        {
            return $this->makeTransfer($input, $this->merchant);
        });
    }

    /**
     * Create and process a payment transfer
     *
     * @param   Payment\Entity          $payment
     * @param   Merchant\Entity         $merchant
     * @param   array                   $input
     * @return  Base\PublicCollection
     */
    public function createForPayment(Payment\Entity $payment, Merchant\Entity $merchant, array $input)
    {
        $transfers = new Base\PublicCollection;

        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

        (new Validator)->validateTransfers($payment, $merchantBalance, $input);

        $totalTransferAmount = 0;

        foreach ($input as $transfer)
        {
            $transfer =$this->makeTransfer($transfer, $payment);

            $totalTransferAmount += $transfer['amount'];

            $transfers->push($transfer);
        }

        $this->updatePaymentAmountTransferred($payment, $totalTransferAmount);

        return $transfers;
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
     * @param  array       $input
     * @param  Base\Entity $source
     * @return Transfer\Entity
     */
    protected function makeTransfer(array $input, Base\Entity $source) : Entity
    {
        (new Validator)->validateInput('transfer', $input);

        if (isset($input[ToType::CUSTOMER]) === true)
        {
            return $this->customerTransfer($source, $input);
        }
        else if (isset($input[ToType::ACCOUNT]) === true)
        {
            return $this->accountTransfer($source, $input);
        }
    }

    /**
     * Transfer to a customer account
     *
     * @param  Base\Entity      $source
     * @param  array            $transferInput
     * @return Transfer\Entity
     */
    protected function customerTransfer(Base\Entity $source, array $transferInput) : Transfer\Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::OPENWALLET);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER,
            ['transfer' => $transferInput]);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($transferInput[ToType::CUSTOMER], $this->merchant);

        $transfer = $this->createTransfer($to, $source, $transferInput, $transferInput['amount']);

        $customerTxn = (new Customer\Transaction\Core)
                        ->createFromCustomerCredit($transfer, $transferInput['amount'], $to->getId());

        $this->repo->saveOrFail($customerTxn);

        return $transfer;
    }

    /**
     * Transfer to a Marketplace account
     *
     * @param  Base\Entity      $source
     * @param  array            $transferInput
     * @return Transfer\Entity
     */
    protected function accountTransfer(Base\Entity $source, array $transferInput) : Transfer\Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT,
            ['transfer' => $transferInput]);

        $originPayment = null;

        $accountId = $transferInput[ToType::ACCOUNT];

        $account = $this->repo
                        ->merchant
                        ->fetchAccountByIdAndMerchant($accountId, $this->merchant);

        if (($source instanceof Payment\Entity) === true)
        {
            $originPayment = $source;

            $this->checkMultipleMarketplaceTransfer($originPayment->getId(), $accountId);

            $input['contact'] = $originPayment->getContact();

            $input['email']   = $originPayment->getEmail();
        }

        $paymentData = [
            Payment\Entity::AMOUNT    => $transferInput['amount'],
            Payment\Entity::CONTACT   => $transferInput['contact'] ?? null,
            Payment\Entity::EMAIL     => $transferInput['email'] ?? null,
            Payment\Entity::CURRENCY  => $transferInput['currency'],
        ];

        $transferPayment = (new Payment\Service)->processTransfer($account, $paymentData, $originPayment);

        $baseAmount = $transferPayment->getBaseAmount();

        $transfer = $this->createTransfer($account, $source, $transferInput, $baseAmount);

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
     * @param  string $paymentId
     * @param  string $accountId
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
