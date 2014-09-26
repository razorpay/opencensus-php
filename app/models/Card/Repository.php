<?php

namespace Models\Card;

use Models\Base;
use Models\Card;

class Repository extends Base\Repository
{
    protected $entity = 'Card';

    public function retrieveDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6));
        }

        //
        // retrieve card details
        //
        $cardDetail = Card\Detail::find($iin);

        return $cardDetail;
    }
}