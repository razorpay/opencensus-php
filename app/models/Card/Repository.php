<?php

namespace Models\Card;

use Models\Base;
use Models\Card;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Card';

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