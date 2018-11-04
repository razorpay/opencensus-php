<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\Merchant\Account;

class Constants
{
    // ------------------- MERCHANT -------------------

    const TEST_MERCHANT                                      = Account::TEST_ACCOUNT;

    const TEST_MERCHANT_BANK_ACCOUNT                         = 'TestMerchantBA';

    const TEST_MERCHANT_DEFAULT_VPA                          = 'TstMerchantVpa';

    const TEST_MERCHANT_BHARATQR_VPA                         = 'TstMrchtVpaBqr';

    const SHARED_MERCHANT                                    = Account::SHARED_ACCOUNT;

    const SHARED_MERCHANT_BANK_ACCOUNT                       = 'SharedMerchaBA';

    const SHARED_MERCHANT_DEFAULT_VPA                        = 'SharedMerchVpa';

    const SHARED_MERCHANT_BHARATQR_VPA                       = 'ShrMrchtVpaBqr';

    // ------------------- CUSTOMERS -------------------

    const GLOBAL_CUSTOMER                                    = 'AglobalCustom1';

    const GLOBAL_CUSTOMER_2                                  = 'AglobalCustom2';

    // Belongs to GLOBAL_CUSTOMER
    const LOCAL_CUSTOMER                                     = 'AlocalCustom01';
    // Belongs to GLOBAL_CUSTOMER_2
    const LOCAL_CUSTOMER_2                                   = 'AlocalCustom02';
    // Does not belong anywhere
    const LOCAL_CUSTOMER_3                                   = 'AlocalCustom03';

    // Belongs to LOCAL_CUSTOMER
    const LOCAL_CUSTOMER_DEVICE                              = 'ALC01device001';
    // Belongs to LOCAL_CUSTOMER_2
    const LOCAL_CUSTOMER_2_DEVICE                            = 'ALC02device001';

    // Belongs to LOCAL_CUSTOMER
    const LOCAL_CUSTOMERL_BANK_ACCOUNT                       = 'ALC01bankAc001';
    // Belongs to LOCAL_CUSTOMER_2
    const LOCAL_CUSTOMER_2_BANK_ACCOUNT                      = 'ALC02bankAc001';

    // Belongs to GLOBAL_CUSTOMER and NO account
    const GLOBAL_CUSTOMER_DEFAULT_VPA                        = 'AGC1defaultVpa';
    // Belongs to GLOBAL_CUSTOMER_2 and NO account
    const GLOBAL_CUSTOMER_2_DEFAULT_VPA                      = 'AGC2defaultVpa';

    // Belongs to LOCAL_CUSTOMER and LOCAL_BANK_ACCOUNT
    const LOCAL_CUSTOMER_VPA                                 = 'ALC01custVpa01';
    // Belongs to LOCAL_CUSTOMER_2 and LOCAL_CUSTOMER_2
    const LOCAL_CUSTOMER_2_VPA                               = 'ALC02custVpa01';

    // ------------------------ TEST DEVICES ------------------------

    const DEVICE_1                                          = 'device_1';
    const DEVICE_2                                          = 'device_2';

}
