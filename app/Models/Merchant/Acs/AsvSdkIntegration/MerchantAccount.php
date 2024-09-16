<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Models\Merchant\Account\Entity as AccountEntity;

class MerchantAccount extends Merchant
{
    protected $entity = AccountEntity::class;

    public function __construct()
    {
        parent::__construct();
    }
}
