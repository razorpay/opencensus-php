<?php

namespace Models\Settlement;

use Models;
use Models\Base;
use Models\Transaction;
use Models\Settlement;

class Merchant
{
    protected $merchant;

    protected $amount;

    public function __construct($merchant, $amount)
    {
        $this->merchant = $merchant;

        $this->amount = $amount;

        $this->merchantRepo = new Models\Merchant\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->setlRepo = new Settlement\Repository;

        // Create settlement transaction
        $this->setlTransaction = $this->newSettlementTransaction();
    }

    public function settle()
    {
        // Create settlement entity
        $setl = $this->newSettlementEntity();

        $this->setlTransaction->entity()->associate($setl);

        // Updates merchant and api balance
        $this->updateBalances();

        // Saves to db
        $this->txnRepo->saveOrFail($this->setlTransaction);
        $this->setlRepo->saveOrFail($setl);

        // Get merchant bank account
        $this->fetchMerchantBankAccount();

        return $setl;
    }

    protected function newSettlementTransaction()
    {
        $txn = new Transaction\Entity;

        $values = array(
            Transaction\Entity::MERCHANT_ID => $this->merchant->getKey(),
            Transaction\Entity::DEBIT       => $this->amount,
            Transaction\Entity::CREDIT      => 0,
            Transaction\Entity::CURRENCY    => 'INR',
            Transaction\Entity::GATEWAY_FEE => 0,
            Transaction\Entity::API_FEE     => 0,
            Transaction\Entity::SETTLED_AT  => time(),
            Transaction\Entity::FEE         => 0,
            Transaction\Entity::AMOUNT      => $this->amount,
            Transaction\Entity::TYPE        => Transaction\Type::SETTLEMENT,
        );

        $txn->fillAndGenerateId($values);

        return $txn;
    }

    protected function newSettlementEntity()
    {
        $setl = (new Settlement\Entity)->generateId();

        $setl->setAmount($this->amount);
        $setl->setStatus('abc');

        $setl->transaction()->associate($this->setlTransaction);
        $setl->merchant()->associate($this->merchant);

        return $setl;
    }

    protected function updateBalances()
    {
        $nodalBalance = $this->merchantRepo->getEscrowBalanceLockForUpdate();

        $merchantBalance = $this->merchantRepo->getBalanceLockForUpdate(
                                                    $this->merchant->getKey());

        $merchantBalance->subAmount($this->setlTransaction->getDebit());
        $nodalBalance->subAmount($this->setlTransaction->getDebit());

        $this->merchantRepo->updateBalance($nodalBalance);
        $this->merchantRepo->updateBalance($merchantBalance);

        $attributes = array(
            Transaction\Entity::BALANCE => $merchantBalance->getBalance(),
            Transaction\Entity::ESCROW_BALANCE => $nodalBalance->getBalance());

        $this->setlTransaction->fill($attributes);
    }

    protected function fetchMerchantBankAccount()
    {
        $mode = \BasicAuth::getMode();

        if ($mode === 'test')
        {
            $ba = null;

            $this->merchant->setRelation('bankAccount', null);
        }
        else
        {
            $ba = $this->merchantRepo->getBankAccount($this->merchant);
        }

        return $ba;
    }
}
