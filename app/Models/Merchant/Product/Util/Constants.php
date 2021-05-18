<?php

namespace RZP\Models\Merchant\Product\Util;

class Constants
{
    const CHECKOUT        = 'checkout';
    const ACCOUNT_CONFIG  = 'account_config';
    const REQUIREMENTS    = 'requirements';
    const PAYMENT_CAPTURE = 'payment_capture';
    const PAYMENT_CONFIG  = 'payment_config';
    const METHODS         = 'methods';
    const CONFIGURATION   = 'configuration';
    const NOTIFICATIONS   = 'notifications';
    const SETTLEMENTS     = 'settlements';
    const BANK_DETAILS    = 'bank_details';
    const FLASH_CHECKOUT  = 'flash_checkout';
    const FEATURES        = 'features';

    const REQUESTED_CONFIGURATION = 'requested_configuration';
    const ACTIVE_CONFIGURATION    = 'active_configuration';

    //Feature names
    const NOFLASHCHECKOUT = 'noflashcheckout';

    //Notifications input constants
    const WHATSAPP = 'whatsapp';
    const SMS      = 'sms';

    //Settlements input constants
    const IFSC_CODE      = 'ifsc_code';
    const ACCOUNT_NUMBER = 'account_number';
    const NAME           = 'name';

    //payment capture input constants
    const MODE                    = 'mode';
    const REFUND_SPEED            = 'refund_speed';
    const AUTOMATIC_EXPIRY_PERIOD = 'automatic_expiry_period';
    const MANUAL_EXPIRY_PERIOD    = 'manual_expiry_period';

    // Product request constants
    const REQUESTED = 'requested';
    const COMPLETED = 'completed';
    const FAILED    = 'failed';

    // Configuration types
    const GENERAL = 'general';
    const PAYMENT_METHODS = 'payment_methods';
}
