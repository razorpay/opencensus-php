<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{

    /************* Constants *******************/
    const CUSTOMER_ID               = 'customer_id';
    const CUSTOMER                  = 'customer';
    const ORDER                     = 'order';
    const GATEWAY_CONFIG            = 'gateway_config';
    const GATEWAYS                  = 'gateways';
    const GATEWAY                   = 'gateway';
    const PRIORITY                  = 'priority';
    const POPULAR_BANKS             = 'popular_banks';

    const ORDER_ID                  = 'order_id';
    const TPV                       = 'tpv';
    const IS_TPV                    = 'is_tpv';
    const RESTRICT_BANK_ACCOUNTS    = 'restrict_bank_accounts';
    const BANK_ACCOUNTS             = 'bank_accounts';
    const SDK_VERSIONS              = 'sdk_versions';
    const ANDROID                   = 'android';
    const IOS                       = 'ios';
    const MIN                       = 'min';
    const BLOCKED                   = 'blocked';

}
