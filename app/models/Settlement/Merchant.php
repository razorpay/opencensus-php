<?php

namespace Models\Settlement;

use Models;
use Models\Base;
use Models\EE\Exception;
use Models\Transaction;
use Models\Settlement;

class Merchant
{
    protected $merchant;

    protected $amount;

    public function __construct($merchant, $channel)
    {
        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->merchantRepo = new Models\Merchant\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->setlRepo = new Settlement\Repository;
    }

    public function settle($txns, $amount, $apiFee, $gatewayFee)
    {
        $this->amount = $amount;

        // Create settlement transaction
        $this->setlTransaction = $this->newSettlementTransaction();

        // Create settlement entity
        $setl = $this->newSettlementEntity();

        $this->setlTransaction->entity()->associate($setl);

        // Updates merchant and api balance
        $this->updateBalances();

        // Saves to db
        $this->txnRepo->saveOrFail($this->setlTransaction);
        $this->setlRepo->saveOrFail($setl);

        $this->txnRepo->updateSettlementId($txns, $setl->getId());

        // Get merchant bank account
        $this->fetchMerchantBankAccount();

        return $setl;
    }

    public function collectApiFees($apiFee, $channel)
    {
        if ($channel === Settlement\Channel::KOTAK)
        {
            throw new Exception\LogicException(
                'Should not have reached here guys!');
        }

        $setl = (new Settlement\Merchant($merchant, $channel))->settle(
                                    $setlTxns, $setlAmount, $setlApiFee, $setlGatewayFee);
    }

    protected function newSettlementTransaction()
    {
        $txn = new Transaction\Entity;

        $values = array(
            Transaction\Entity::DEBIT       => $this->amount,
            Transaction\Entity::CREDIT      => 0,
            Transaction\Entity::CURRENCY    => 'INR',
            Transaction\Entity::GATEWAY_FEE => 0,
            Transaction\Entity::API_FEE     => 0,
            Transaction\Entity::SETTLED     => 1,
            Transaction\Entity::SETTLED_AT  => time(),
            Transaction\Entity::FEE         => 0,
            Transaction\Entity::AMOUNT      => $this->amount,
            Transaction\Entity::TYPE        => Transaction\Type::SETTLEMENT,
            Transaction\Entity::CHANNEL     => $this->channel
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($this->merchant);

        return $txn;
    }

    protected function newSettlementEntity()
    {
        $setl = (new Settlement\Entity)->generateId();

        $setl->setAmount($this->amount);
        $setl->setStatus(Status::CREATED);
        $setl->setChannel($this->channel);

        $setl->transaction()->associate($this->setlTransaction);
        $setl->merchant()->associate($this->merchant);

        return $setl;
    }

    protected function updateBalances()
    {
        return (new Transaction\Core)->updateBalances($this->setlTransaction);
    }

    protected function fetchMerchantBankAccount()
    {
        $mode = \BasicAuth::getMode();

        if ($mode === 'test')
        {
            $ba = $this->getDefaultBank($this->merchant);
        }
        else
        {
            $ba = $this->merchantRepo->getBankAccount($this->merchant);

            if ($ba === null)
            {
                throw new Exception\LogicException(
                    'Merchant bank account not found');
            }
        }

        return $ba;
    }

    protected function getDefaultBank($merchant)
    {
        $attributes = array(
            'merchant_id'   => $merchant->getId(),
            'ifsc_code'     => 'RZPB0000000',
            'beneficiary_name' => $merchant['name'],
            'account_number'   => '10101030103');

        $ba = (new \Models\Merchant\BankAccount)->newInstance($attributes, true);

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        return $ba;
    }
}
