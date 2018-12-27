<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'balance';

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::ACCOUNT_NUMBER  => 'sometimes|alpha_num',
    ];

    protected $appFetchParamRules = [
        Entity::ACCOUNT_NUMBER => 'sometimes|alpha_num',
    ];

    public function findOrFail($id, $columns = array('*'))
    {
        return $this->newQuery()
                    ->merchantIdAndType($id)
                    ->firstOrFail();
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        return $this->newQuery()
                    ->merchantIdAndType($id)
                    ->firstOrFailPublic();
    }

    public function getBalanceLockForUpdate($id)
    {
        assert ($this->isTransactionActive());

        return Entity::lockForUpdate()->newQuery()
                                      ->merchantIdAndType($id)
                                      ->firstOrFail();
    }

    // not in use
    public function getMerchantBalanceLockForUpdate($merchant)
    {
        assert ($this->isTransactionActive());

        return $this->getBalanceLockForUpdate($merchant->getKey());
    }

    public function getMerchantBalance($merchant): Entity
    {
        $balance = $this->findOrFailPublic($merchant->getId());

        $balance->merchant()->associate($merchant);

        return $balance;
    }

    public function editMerchantAmountCredits($merchant, $amountCredits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function () use ($merchant, $amountCredits, $channel)
        {
            return $this->editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel);
        });
    }

    private function editMerchantAmountCreditsInTransaction($merchant, $amountCredits, $channel)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);
        //
        // $nodalCredits = $nodalBalance->getAmountCredits();
        //
        // $nodalCredits = $nodalCredits - $balance->getAmountCredits() + $amountCredits;
        //
        // $nodalBalance->setAmountCredits($nodalCredits);

        $balance->setAmountCredits($amountCredits);

        $balance->saveOrFail();

        // $nodalBalance->saveOrFail();

        return $balance;
    }

    public function editMerchantFeeCredits($merchant, $feeCredits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function () use ($merchant, $feeCredits, $channel)
        {
            return $this->editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel);
        });
    }

    public function editMerchantRefundCredits($merchant, $credits)
    {
        $channel = $merchant->getChannel();

        return $this->transaction(function () use ($merchant, $credits, $channel)
        {
            return $this->editMerchantRefundCreditsInTransaction($merchant, $credits, $channel);
        });
    }

    private function editMerchantFeeCreditsInTransaction($merchant, $feeCredits, $channel)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        $balance->setFeeCredits($feeCredits);

        $balance->saveOrFail();

        return $balance;
    }

    private function editMerchantRefundCreditsInTransaction($merchant, $credits, $channel)
    {
        assert ($this->isTransactionActive());

        $balance = $this->findOrFail($merchant->getId());

        $balance->setRefundCredits($credits);

        $balance->saveOrFail();

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

        return $this->findOrFail(Merchant\Account::NODAL_ACCOUNT);
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

    /**
     * Returns merchant ids where updated at > $minUpdatedAtTimeStamp
     *
     * @param int $minUpdatedAtTimeStamp
     *
     * @return array
     */
    public function getMerchantsIdsForEsSync(int $minUpdatedAtTimeStamp): array
    {
        $merchantIds = $this->newQuery()
                            ->where(Entity::UPDATED_AT, '>=', $minUpdatedAtTimeStamp)
                            ->where(Entity::TYPE, '=', Type::PRIMARY)
                            ->groupBy(Entity::MERCHANT_ID)
                            ->select(Entity::MERCHANT_ID)
                            ->get();

        $merchantIds = isset($merchantIds) ? $merchantIds->toArray() : [];

        $merchantIds = array_pluck($merchantIds, Entity::MERCHANT_ID);

        return $merchantIds;
    }

    public function getBalances($limit)
    {
        return $this->newQuery()
                    ->whereRaw(Entity::ID. '=' . Entity::MERCHANT_ID)
                    ->limit($limit)
                    ->get();
    }

    /**
     * @param string      $merchantId
     * @param string      $balanceType
     * @param string|null $connection
     * @return mixed
     */
    public function getMerchantBalanceByType(string $merchantId, string $balanceType, string $connection = null)
    {
        $query = $connection !== null ? $this->newQueryWithConnection($connection) : $this->newQuery();

        return $query->merchantIdAndType($merchantId, $balanceType)
                     ->first();
    }

    public function getBalanceIdByAccountNumberOrFail(string $accountNumber): string
    {
        return $this->getBalanceByAccountNumberOrFail($accountNumber)->getId();
    }

    public function getBalanceByAccountNumberOrFail(string $accountNumber): Entity
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->merchantIdAndType($this->merchant->getId(), Type::BANKING)
                    ->firstOrFailPublic();
    }
}
