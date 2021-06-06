<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Exception;
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

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }
}
