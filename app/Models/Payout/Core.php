<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Payment;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Transaction;
use RZP\Models\Base\Traits\BatchSettlementTrait;

class Core extends Base\Core
{
    use BatchSettlementTrait;

    const MUTEX_RESOURCE        = 'PAYOUT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a direct payout - source from merchant balance
     *
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return Payout\Entity
     */
    public function directPayout(array $input, Merchant\Entity $merchant)
    {
        $payout = $this->createPayout($input, $merchant);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    /**
     * Create a payment payout - from a source payment
     *
     * @param  array           $input
     * @param  Payment\Entity  $payment
     * @param  Merchant\Entity $merchant
     * @return Payout\Entity
     */
    public function paymentPayout(array $input, Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $payout = $this->createPayout($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    /**
     * Initiate bank transfers for payouts
     *
     * @param  array  $input
     * @param  string $channel
     * @return array
     */
    public function initiatePayouts(array $input, string $channel) : array
    {
        return $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function() use($input, $channel)
            {
                return $this->processBankPayouts($input, $channel);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_ANOTHER_OPERATION_IN_PROGRESS);
    }

    protected function createPayout(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->validateMerchantStatus($merchant);

        return $this->repo->transaction(function () use ($input, $merchant)
        {
            $payout = $this->createPayoutEntity($input, $merchant);

            $this->updatePayoutWithTxn($payout);

            return $payout;
        });
    }

    protected function processBankPayouts(array $input, string $channel) : array
    {
        return $this->repo->transaction(function() use ($input, $channel)
        {
            $timestamp = Carbon::now('Asia/Kolkata')->timestamp;

            $payouts = $this->repo->payout->fetchCreatedPayouts($timestamp, Method::FUND_TRANSFER);

            $this->updatePayoutStatus($payouts, Status::INITIATED);

            $method = 'processBankPayoutsFor' . ucfirst($channel);

            // Calls $this->processBankPayoutsForKotak()
            $data[$channel] = $this->$method($payouts);

            $this->saveEntitiesToDb($payouts);

            return $data;
        });
    }

    protected function processBankPayoutsForKotak(Base\PublicCollection $payouts) : array
    {
        $data['channel'] = 'kotak';

        $data['count'] = $payouts->count();

        if ($payouts->count() === 0)
        {
            $data['message'] = 'No payouts to process';

            return $data;
        }

        foreach ($payouts as $payout)
        {
            $this->createOrUpdateBatchSettlementForEntity($payout, 1);

            $payout->batchSettlement()->associate($this->batchSettlement);
        }

        $urlText = (new Kotak\NodalAccount)->getPayoutsFile($payouts);

        $urls = [
            'kotak_payout_txt'   => $urlText,
        ];

        $this->updateBatchSettlementEntityUrls($urls);

        $data['payout_text_file'] = $urlText;

        return $data;
    }

    protected function updatePayoutStatus(Base\PublicCollection $payouts, string $status)
    {
        $this->repo->payout->updateStatus($payouts, $status);
    }

    protected function saveEntitiesToDb(Base\PublicCollection $payouts)
    {
        foreach ($payouts as $payout)
        {
            $this->repo->saveOrFail($payout);
        }
    }

    protected function createPayoutEntity(array $input, Merchant\Entity $merchant) : Entity
    {
        $payout = (new Entity)->build($input);

        $customer = $this->getCustomer($input, $merchant);

        $destination = $this->getPayoutDestination($input, $merchant, $customer);

        $payout->setChannel(Settlement\Channel::KOTAK);

        $payout->merchant()->associate($merchant);

        $payout->customer()->associate($customer);

        $payout->destination()->associate($destination);

        return $payout;
    }

    protected function getCustomer(array $input, Merchant\Entity $merchant) : Customer\Entity
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $merchant);

        return $customer;
    }

    protected function getPayoutDestination(array $input, Merchant\Entity $merchant, Customer\Entity $customer)
    {
        $destId = $input[Entity::DESTINATION];

        if ($input[Entity::METHOD] === Method::FUND_TRANSFER)
        {
            $destination = $this->repo->bank_account->findByPublicIdAndMerchant($destId, $merchant);

            // Check if the bank account destination is linked to the customer
            if ($destination->getEntityId() !== $customer->getId())
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid destination_id: " . $destination->getPublicId());
            }
        }

        return $destination;
    }

    protected function updatePayoutWithTxn(Entity $payout)
    {
        $txnCore = new Transaction\Core;

        $txn = $txnCore->createFromPayout($payout);

        $payout->setFees($txn->getFee());

        $payout->setServiceTax($txn->getServiceTax());

        $this->validateMerchantBalance($payout);

        $txnCore->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);
    }

    protected function validateMerchantBalance(Entity $payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFees();

        $hasBalance = (new Merchant\Balance\Core)
                           ->checkMerchantBalance($payout->merchant, $debitAmount);

        if ($hasBalance === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE);
        }
    }

    protected function validateMerchantStatus(Merchant\Entity $merchant)
    {
        $onHold = $merchant->holdFunds();

        if ($onHold === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }
    }
}
