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
    public function createTransfer(Base\Entity $to, $source, array $input, int $baseAmount) : Entity
    {
        $transferData = [
            Entity::TO_ID           => $to->getId(),
            Entity::TO_TYPE         => $to->getEntityName(),
            Entity::SOURCE_ID       => $source->getId(),
            Entity::SOURCE_TYPE     => $source->getEntityName(),
            Entity::AMOUNT          => $input['amount'],
            Entity::CURRENCY        => $input['currency'],
        ];

        $transfer = (new Entity)->build($transferData);

        $transfer->generateId();

        $transfer->setBaseAmount($baseAmount);

        $transfer->merchant()->associate($source->merchant);

        $txn = (new Transaction\Core)->createFromTransfer($transfer, $to);

        $this->repo->saveOrFail($txn);

        $transfer->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        return $transfer;
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
            if (isset($transfer[ToType::CUSTOMER]) === true)
            {
                $transfer = $this->customerTransfer($payment, $transfer);
            }
            else if (isset($transfer[ToType::ACCOUNT]) === true)
            {
                $transfer = $this->accountTransfer($payment, $transfer);
            }

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
     * Transfer to a customer account
     *
     * @param  Payment\Entity   $payment
     * @param  array            $transferInput
     * @return Transfer\Entity
     */
    protected function customerTransfer(Payment\Entity $payment, array $transferInput) : Transfer\Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::OPENWALLET);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_CUSTOMER,
            ['transfer' => $transferInput]);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($transferInput[ToType::CUSTOMER], $this->merchant);

        $transfer = $this->createTransfer($to, $payment, $transferInput, $transferInput['amount']);

        $customerTxn = (new Customer\Transaction\Core)
                        ->createFromCustomerCredit($payment, $transfer, $transferInput['amount'], $to->getId());

        $this->repo->saveOrFail($customerTxn);

        return $transfer;
    }

    /**
     * Transfer to a Marketplace account
     *
     * @param  Payment\Entity   $payment
     * @param  array            $transferInput
     * @return Transfer\Entity
     */
    protected function accountTransfer(Payment\Entity $payment, array $transferInput) : Transfer\Entity
    {
        $this->verifyFeatureAllowed(Feature\Constants::MARKETPLACE);

        $accountId = $transferInput[ToType::ACCOUNT];

        $amount = $transferInput['amount'];

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_TO_ACCOUNT,
            ['transfer' => $transferInput]);

        $this->checkMultipleMarketplaceTransfer($payment->getId(), $accountId);

        $account = $this->repo
                        ->merchant
                        ->fetchAccountByIdAndMerchant($accountId, $this->merchant);

        $paymentData = [
            Payment\Entity::AMOUNT    => $transferInput['amount'],
            Payment\Entity::CONTACT   => $payment->getContact(),
            Payment\Entity::EMAIL     => $payment->getEmail(),
            Payment\Entity::CURRENCY  => $transferInput['currency'],
        ];

        $transferPayment = (new Payment\Service)->processTransfer($account, $payment, $paymentData);

        $baseAmount = $transferPayment->getBaseAmount();

        $transfer = $this->createTransfer($account, $payment, $transferInput, $baseAmount);

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
