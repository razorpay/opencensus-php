<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;

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
        return Entity::findOrFailPublic($merchant->getId());
    }

    public function editMerchantFreeCredits($merchant, $freeCredits)
    {
        return $this->transaction(function () use ($merchant, $freeCredits)
        {
            return $this->editMerchantFreeCreditsInTransaction($merchant, $freeCredits);
        });
    }

    private function editMerchantFreeCreditsInTransaction($merchant, $freeCredits)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());
        $nodalBalance = $this->getNodalBalanceLockForUpdate('kotak');

        $nodalCredits = $nodalBalance->getCredits();
        $nodalCredits = $nodalCredits - $balance->getCredits() + $freeCredits;
        $nodalBalance->setCredits($nodalCredits);

        $balance->setCredits($freeCredits);

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
