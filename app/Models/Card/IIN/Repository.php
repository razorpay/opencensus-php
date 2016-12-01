<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Base;
use RZP\Models\Card;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'iin';

    protected $appFetchParamRules = array(
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::NETWORK         => 'sometimes|alpha_space',
        Entity::INTERNATIONAL   => 'sometimes|in:0,1',
        Entity::EMI             => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|string|in:debit,credit,unknown',
        Entity::OTP_READ        => 'sometimes|in:0,1',
        Entity::ISSUER          => 'sometimes|string',
    );

    protected function addQueryOrder($query)
    {
        ;
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}
