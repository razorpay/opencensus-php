<?php

namespace RZP\Models\Payout;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\BankAccount;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Payout;
use RZP\Models\Payout\Entity;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    public function createPayout($input, $merchant)
    {
        $this->repo->payout->beginTransaction();

        try
        {
            $payout = $this->createPayoutEntity($input, $merchant);

            $this->updatePayoutWithTxn($payout);

            $this->repo->payout->commit();

            return $payout;
        }
        catch (\Exception $e)
        {
            $this->repo->payout->rollback();

            throw $e;
        }
    }

    protected function createPayoutEntity($input, $merchant)
    {
        $customer = $this->getCustomer($input, $merchant);

        $destination = $this->getPayoutDestination($input, $merchant, $customer);

        //create payout entity
        $payout = (new Payout\Entity)->build($input);

        $payout->setChannel(Settlement\Channel::KOTAK);

        //set relations
        $payout->merchant()->associate($merchant);

        $payout->customer()->associate($customer);

        $payout->dest()->associate($destination);

        return $payout;
    }

    // check if valid customer for merchant and return
    protected function getCustomer(& $input, $merchant)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        Customer\Entity::verifyIdAndStripSign($customerId);

        $customer = $this->repo->customer->findByIdAndMerchantId($customerId, $merchant->getId());

        unset($input[Entity::CUSTOMER_ID]);

        return $customer;
    }

    protected function getPayoutDestination(& $input, $merchant, $customer)
    {
        //check if valid bank account for merchant
        $destId = $input[Entity::DESTINATION];

        if ($input[Payout\Entity::METHOD] === Payout\Method::FUND_TRANSFER)
        {
            BankAccount\Entity::verifyIdAndStripSign($destId);

            $destination = $this->repo->bank_account->findByIdAndMerchantId($destId, $merchant->getId());

            //check if valid destination for customer
            if ($destination->getEntityId() !== $customer->getId())
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid destination id" . $destination->getPublicId());
            }
        }

        unset($input[Entity::DESTINATION]);

        return $destination;
    }

    protected function validateMerchantBalance($payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFee();

        $hasBalance = (new Merchant\Balance\Core)->checkMerchantBalance(
            $payout->merchant, $debitAmount);

        if ($hasBalance === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE);
        }
    }

    protected function updatePayoutWithTxn($payout)
    {
        $txn = (new Transaction\Core)->createFromPayout($payout);

        $payout->setFee($txn->getFee());

        $payout->setServiceTax($txn->getServiceTax());

        $this->validateMerchantBalance($payout);

        (new Transaction\Core)->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($payout);

        return $payout;
    }
}