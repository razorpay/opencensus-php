<?php

namespace Models\Card;

use Models\Base;
use Models\Card;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Card';

    protected $appFetchParamRules = array(
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::LAST4           => 'sometimes|integer|digits:4',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::NETWORK         => 'sometimes|alpha_space',
    );

    public function retrieveIinDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6));
        }

        //
        // retrieve iin details
        //
        return Card\Detail::find($iin);
    }
}