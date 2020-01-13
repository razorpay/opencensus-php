<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Repository extends Base\Repository
{
    protected $entity = 'payout_link';
}
