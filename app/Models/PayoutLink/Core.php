<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\PayoutLink\Entity as PayoutLink;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        #todo: pl fill this function
        // This will also create the contact if necessary

        $input = [
            PayoutLink::CONTACT_ID      => 'BXV5GAmaJEcGr1',
            PayoutLink::FUND_ACCOUNT_ID => 'D6Z9Jfir2egAUT',
            PayoutLink::SHORT_URL       => 'http://rzp.io/faking_url',
            PayoutLink::MERCHANT_ID     => 'D5mLvCOvhuV1hN',
            PayoutLink::STATUS          => Status::ISSUED,
            PayoutLink::AMOUNT          => 1000,
        ];

        $payout_link = (new PayoutLink)->build($input);

        $payout_link->saveOrFail();

    }
}
