<?php

namespace RZP\Models\WalletAccount;

use RZP\Models\Base;
use RZP\Models\Feature\Constants as Features;

class Service extends Base\Service
{
    public function isWalletAccountAmazonPayFeatureDisabled(): bool
    {   
        return ($this->merchant->isFeatureEnabled(Features::DISABLE_X_AMAZONPAY) === true);
    }  
}
