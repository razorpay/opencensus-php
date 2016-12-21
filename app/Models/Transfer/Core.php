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
use RZP\Models\Merchant;

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
     * @param  Transaction\Entity $transaction Transaction Entity
     * @return Transfer\Entity
     */
    public function createTransfer(Base\Entity $to, $source, $amount)
    {
        $transferData = [
            Entity::TO_ID           => $to->getId(),
            Entity::TO_TYPE         => $to->getEntityName(),
            Entity::SOURCE_ID       => $source->getId(),
            Entity::SOURCE_TYPE     => $source->getEntityName(),
            Entity::AMOUNT          => $amount,
        ];

        $transfer = (new Entity)->build($transferData);

        $transfer->generateId();

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

        $totalTransferAmount = $this->getTotalTransferAmount($input);

        $this->updatePaymentTransferAmount($payment, $totalTransferAmount);

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

            $transfers->push($transfer);
        }

        return $transfers;
    }

    public function createForRefund(Payment\Entity $payment, array $input)
    {

    }

    protected function getTotalTransferAmount(array $input)
    {
        $amount = 0;

        foreach ($input as $transfer)
        {
            $amount += $transfer['amount'];
        }

        return $amount;
    }

    protected function updatePaymentTransferAmount($payment, int $amount)
    {
        $this->mutex->acquireAndRelease($payment->getId(), function() use ($payment, $amount)
        {
            $payment->transferAmount($amount);

            $this->repo->saveOrFail($payment);
        });
    }

    protected function customerTransfer($payment, $merchant, $transfer)
    {
        $this->verifyFeatureAllowed(Merchant\Features::B2BWALLET);

        $to = $this->repo
                   ->customer
                   ->findByPublicIdAndMerchant($transfer[ToType::CUSTOMER], $merchant);

        $transfer = $this->createTransfer($to, $payment, $transfer['amount']);

        $amount = $transfer->transaction->getAmount();

        $customerTxn = (new Customer\Transaction\Core)
                        ->createFromCustomerCredit($payment, $transfer, $amount, $to->getId());

        $this->repo->saveOrFail($customerTxn);

        return $transfer;
    }

    protected function accountTransfer($payment, $transfer)
    {
        $this->verifyFeatureAllowed(Merchant\Features::MARKETPLACE);

        // Merchant Account ID to receive the transfer
        $accountId = $transfer[ToType::ACCOUNT];

        $amount = $transfer['amount'];

        // Validate account belongs to marketplace

        $account = $this->repo
                        ->merchant
                        ->fetchAccountByIdAndMerchant($accountId, $this->merchant);

        // $vendor->getValidator()->validateVendorForTransfer();

        $transfer = $this->createTransfer($account, $payment, $amount);

        $paymentData = $this->getTransferPaymentData($payment, $amount, $accountId);

        (new Payment\Service)->processTransfer($account, $payment, $amount, $paymentData);

        return $transfer;
    }

    protected function verifyFeatureAllowed(string $feature)
    {
        $merchant = $this->merchant;

        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    "$feature is not supported");
        }

    }


    // Unused. @todo remove
    protected function getTransferPaymentData($payment, int $amount, string $accountId) : array
    {
        return [
            'method'        => 'transfer',
            'amount'        => $amount,
            'currency'      => 'INR',
            'account_id'    => $accountId,
            'contact'       => $payment->getContact(),
            'email'         => $payment->getEmail(),
        ];
    }
}
