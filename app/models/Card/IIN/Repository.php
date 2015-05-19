<?php

namespace Models\Card\IIN;

use Models\Base;
use Models\Card;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

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