<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    use CacheQueries;

    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'methods';

    protected $appFetchParamRules = array(
        Entity::AMEX                => 'sometimes|in:0,1',
        Entity::DISABLED_BANKS      => 'sometimes|in:0,1',
        Entity::CARD                => 'sometimes|in:0,1',
        Entity::EMI                 => 'sometimes|numeric',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::MOBIKWIK            => 'sometimes|in:0,1',
        Entity::PAYTM               => 'sometimes|in:0,1',
        Entity::PAYUMONEY           => 'sometimes|in:0,1',
        Entity::PAYZAPP             => 'sometimes|in:0,1',
        Entity::OLAMONEY            => 'sometimes|in:0,1',
        Entity::AIRTELMONEY         => 'sometimes|in:0,1',
        Entity::AMAZONPAY           => 'sometimes|in:0,1',
        Entity::FREECHARGE          => 'sometimes|in:0,1',
        Entity::CARD_NETWORKS       => 'sometimes|numeric',
        Entity::UPI_TYPE            => 'sometimes|numeric',
        Entity::DEBIT_EMI_PROVIDERS => 'sometimes|numeric',
        Entity::ITZCASH             => 'sometimes|in:0,1',
        Entity::OXIGEN              => 'sometimes|in:0,1',
        Entity::AMEXEASYCLICK       => 'sometimes|in:0,1',
        Entity::PAYCASH             => 'sometimes|in:0,1',
        Entity::CITIBANKREWARDS => 'sometimes|in:0,1',
    );

    public function getMethodsForMerchant(Merchant\Entity $merchant)
    {
        $methods = $this->find($merchant->getId());

        if ($methods !== null)
        {
            $methods->merchant()->associate($merchant);

            $merchant->setRelation('methods', $methods);
        }

        return $methods;
    }

    public function isUpiEnabledForMerchant(Merchant\Entity $merchant)
    {
        $query = $this->newQuery()
                    ->select(Entity::UPI)
                    ->where(Entity::MERCHANT_ID, $merchant->getId());

        return $query->pluck(Entity::UPI)
            ->first();
    }

    public function fetchMethodsToUpdateHdfcDebitEmiValue($count)
    {
        $debitEmiProvider = $this->dbColumn(Entity::DEBIT_EMI_PROVIDERS);

        return $this->newQuery()
                    ->take($count)
                    ->whereNull($debitEmiProvider)
                    ->get();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    protected function addQueryParamItzcash($query,$params)
    {
        $this->queryParamForAdditionalWallets($query,Entity::ITZCASH,$params[Entity::ITZCASH]);
    }

    protected function addQueryParamOxigen($query,$params)
    {
        $this->queryParamForAdditionalWallets($query,Entity::OXIGEN,$params[Entity::OXIGEN]);
    }

    protected function addQueryParamAmexeasyclick($query,$params)
    {
        $this->queryParamForAdditionalWallets($query,Entity::AMEXEASYCLICK,$params[Entity::AMEXEASYCLICK]);
    }

    protected function addQueryParamPaycash($query,$params)
    {
        $this->queryParamForAdditionalWallets($query,Entity::PAYCASH,$params[Entity::PAYCASH]);
    }

    protected function addQueryParamCitibankrewards($query,$params)
    {
        $this->queryParamForAdditionalWallets($query,Entity::CITIBANKREWARDS,$params[Entity::CITIBANKREWARDS]);
    }

    protected function queryParamForAdditionalWallets(&$query,$wallet,$value)
    {
        $additional_wallets = $this->dbColumn(Entity::ADDITIONAL_WALLETS);
        if((bool)$value)
        {
            $query->where($additional_wallets,'like','%'.$wallet.'%');
        } else
        {
            $query->where($additional_wallets,'not like','%'.$wallet.'%');
        }
    }
}
