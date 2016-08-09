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

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::MERCHANT_ID             => 'sometimes|string',
    );

    // These are proxy allowed params to search on.
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
                    ->merchantId($merchant->getId())
                    ->exists();
    }

    public function validateCamapignCreditsNotAssigned($campaign, Merchant\Entity $merchant)
    {
        // Check if the log already exists, API is meant to be used for creation only.
        $creditsLog = $this->newQuery()
            ->where(Entity::CAMPAIGN, '=', $campaign)
            ->merchantId($merchant->getId())
            ->first();

        if ($creditsLog)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The campaign credits has already been assigned to merchant. ' .
                'Credits Id: ' . $creditsLog->getId());
        }
    }
}
