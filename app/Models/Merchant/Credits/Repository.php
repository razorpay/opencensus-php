<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'credits';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = array(
        Entity::ID                      => 'sometimes|string',
    );

    // These are proxy allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::MERCHANT_ID             => 'sometimes|string',
    );

    // These are admin allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
    );

    /**
     * Checks if a record exists by Merchant ID and Campaign Name
     * in credits table.
     *
     * @return bool
     */
    public function creditsLogExists($campaign, Merchant\Entity $merchant)
    {
         return $this->newQuery()
             ->where(Entity::CAMPAIGN, '=', $campaign)
             ->where(Entity::MERCHANT_ID, '=',  $merchant->getId())
             ->exists();
    }
}
