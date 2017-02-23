<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Payment;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    public function directPayout(array $input, Merchant\Entity $merchant)
    {
        $payout = $this->createPayout($input, $merchant);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    public function paymentPayout(array $input, Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $payout = $this->createPayout($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    public function initiatePayouts(array $input, string $channel) : array
    {
        return $this->repo->transaction(function() use ($input, $channel)
        {
            $timestamp = Carbon::today('Asia/Kolkata')->timestamp;

            $payouts = $this->repo->payout->fetchCreatedPayouts($timestamp, Method::FUND_TRANSFER);

            $payouts = $this->repo->payout->fetchAssociatedRelations($payouts, 'dest', 'destination', 'type');

            $this->updatePayoutStatus($payouts);

            $data['kotak'] = $this->processBankPayoutsForKotak($payouts);

            $this->saveEntitiesToDb($payouts);

            return $data;

        });
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

    protected function processBankPayoutsForKotak($payouts)
    {
        $data['channel'] = 'kotak';

        $data['count'] = $payouts->count();

        if ($payouts->count() > 0)
        {
            $urlText = (new Kotak\NodalAccount)->getPayoutsFile($payouts);

            $data['payout_text_file'] = $urlText;
        }
        else
        {
            $data['message'] = 'no payout to process';
        }

        return $data;
    }

    protected function updatePayoutStatus($payouts)
    {
        foreach ($payouts as $payout)
        {
            $payout->setStatus(Status::INITIATED);
        }
    }

    protected function saveEntitiesToDb($payouts)
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

        $type = Method::getEntityName($input[Entity::METHOD]);

        //create payout entity
        $payout = (new Entity)->build($input);

        $payout->setChannel(Settlement\Channel::KOTAK);

        $payout->setType($type);

        //set relations
        $payout->merchant()->associate($merchant);

        $payout->customer()->associate($customer);

        $payout->dest()->associate($destination);

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
                    "Invalid destination id" . $destination->getPublicId());
            }
        }

        return $destination;
    }

    protected function updatePayoutWithTxn(Entity $payout)
    {
        $txnCore = new Transaction\Core;

        $txn = $txnCore->createFromPayout($payout);

        $payout->setFee($txn->getFee());

        $payout->setServiceTax($txn->getServiceTax());

        $this->validateMerchantBalance($payout);

        $txnCore->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);
    }

    protected function validateMerchantBalance(Entity $payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFee();

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
                ErrorCode::BAD_REQUEST_PAYOUT_MERCHANT_FUNDS_ON_HOLD);
        }
    }
}
