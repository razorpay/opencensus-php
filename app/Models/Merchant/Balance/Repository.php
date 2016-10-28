<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'balance';

    // protected $appFetchParamRules = array(
    //     Entity::MERCHANT_ID     => 'sometimes|alpha_num',
    // );

    public function getBalanceLockForUpdate($id)
    {
        assert ($this->isTransactionActive());

        return Entity::lockForUpdate()->findOrFail($id);
    }

    public function getMerchantBalanceLockForUpdate($merchant)
    {
        assert ($this->isTransactionActive());

        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function getMerchantBalance($merchant)
    {
        $balance = $this->findOrFailPublic($merchant->getId());

        $balance->merchant()->associate($merchant);

        return $balance;
    }

    public function editMerchantAmountCredits($merchant, $amountCredits)
    {
        $channel = Settlement\Channel::KOTAK;

        return $this->transaction(function () use ($merchant, $amountCredits, $channel)
        {
            return $this->editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel);
        });
    }

    private function editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());
        $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);

        $nodalCredits = $nodalBalance->getAmountCredits();
        $nodalCredits = $nodalCredits - $balance->getAmountCredits() + $amountCredits;
        $nodalBalance->setAmountCredits($nodalCredits);

        $balance->setAmountCredits($amountCredits);

        $balance->saveOrFail();
        $nodalBalance->saveOrFail();

        return $balance;
    }

    public function editMerchantFeeCredits($merchant, $feeCredits)
    {
        $channel = Settlement\Channel::KOTAK;

        return $this->transaction(function () use ($merchant, $feeCredits, $channel)
        {
            return $this->editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel);
        });
    }

    private function editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());
        $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);

        $nodalCredits = $nodalBalance->getFeeCredits();
        $nodalCredits = $nodalCredits - $balance->getFeeCredits() + $feeCredits;
        $nodalBalance->setFeeCredits($nodalCredits);

        $balance->setFeeCredits($feeCredits);

        $balance->saveOrFail();
        $nodalBalance->saveOrFail();

        return $balance;
    }

    public function updateBalance($balance)
    {
        assert ($this->isTransactionActive());

        $balance->saveOrFail();
    }

    public function createBalance($balance)
    {
        assert ($balance->exists === false);

        $balance->saveOrFail();
    }

    public function getNodalBalance($channel)
    {
        $func = 'get'.ucfirst($channel).'Balance';

        return $this->$func();
    }

    public function getKotakBalance()
    {
        assert ($this->isTransactionActive());

        return $this->newQuery()
                    ->findOrFail(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getNodalBalanceLockForUpdate($channel)
    {
        $func = 'get'.ucfirst($channel).'BalanceLockForUpdate';

        return $this->$func();
    }

    public function getKotakBalanceLockForUpdate()
    {
        assert ($this->isTransactionActive());

        return $this->getBalanceLockForUpdate(Merchant\Account::NODAL_ACCOUNT);
    }

    public function getAtomBalanceLockForUpdate()
    {
        assert ($this->isTransactionActive());

        return $this->getBalanceLockForUpdate(Merchant\Account::ATOM_ACCOUNT);
    }
}
