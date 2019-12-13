<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payout_link';

    public function getActiveFundAccountDetailsOfAssociatedContact($payoutLinkId)
    {
        return $this->contact;
    }
}
