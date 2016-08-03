<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Models\Base;
use RZP\Models\Merchant\FreeCredits;
use RZP\Exception;

class Repository extends Base\Repository
{
//    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'free_credits';

    protected $appFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::MERCHANT_ID             => 'sometimes|string',
    );

    protected $proxyFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
    );

    /**
     * Checks if a record exists by Merchant ID and Campaign Name
     * in free_credits table
     *
     * @return bool
     */
    public function findByCampaignAndMerchantId($campaign, $merchant)
    {
         return $this->newQuery()
             ->where(Entity::CAMPAIGN, '=', $campaign)
             ->where(Entity::MERCHANT_ID, '=',  $merchant->getId())
             ->exists();
    }

}
