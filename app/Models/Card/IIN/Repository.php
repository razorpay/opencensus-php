<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Base;
use RZP\Models\Card;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'IIN';

    protected function addQueryOrder($query)
    {
        ;
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}