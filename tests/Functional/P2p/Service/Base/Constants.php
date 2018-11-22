<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\Merchant\Account;

class Constants
{
    // ------------------- MERCHANT -------------------

    const TEST_MERCHANT                                      = Account::TEST_ACCOUNT;
    const DEMO_MERCHANT                                      = Account::DEMO_ACCOUNT;
    const SHARED_MERCHANT                                    = Account::SHARED_ACCOUNT;

    // -------------------- HANDLES  -------------------

    const RAZOR_SHARP                                       = 'razorsharp';
    const RZP_SHARP                                         = 'rzpsharp';
    const NORZP_SHARP                                       = 'norzpsharp';

    // --------------------- BANKS ---------------------

    const ARZP                                              = 'ARZP';
    const BRZP                                              = 'BRZP';
    const CRZP                                              = 'CRZP';

    // -------------------- GATEWAY --------------------

    const P2P_UPI_SHARP                                     = 'p2p_upi_sharp';

    // ------------------- CUSTOMERS -------------------

    const RZP_LOCAL_CUSTOMER_1                               = 'ArzpLocalCust1';
    const RZP_LOCAL_CUSTOMER_2                               = 'ArzpLocalCust2';

    const CUSTOMER_1_DEVICE_1                                = 'ALC01device001';
    const CUSTOMER_2_DEVICE_1                                = 'ALC02device001';

    const CUSTOMER_1_DEVICE_TOKEN_1                          = 'ALC01DevTok001';
    const CUSTOMER_1_DEVICE_TOKEN_2                          = 'ALC01DevTok002';

    const CUSTOMER_2_DEVICE_TOKEN_1                          = 'ALC02DevTok001';
    const CUSTOMER_2_DEVICE_TOKEN_2                          = 'ALC02DevTok002';

    const CUSTOMER_1_BANK_ACCOUNT_1                          = 'ALC01bankAc001';
    const CUSTOMER_2_BANK_ACCOUNT_1                          = 'ALC02bankAc001';

    const CUSTOMER_1_VPA_1                                   = 'ALC01custVpa01';
    const CUSTOMER_1_VPA_2                                   = 'ALC01custVpa02';
    const CUSTOMER_2_VPA_1                                   = 'ALC02custVpa01';
    const CUSTOMER_2_VPA_2                                   = 'ALC02custVpa02';

    // ------------------------ TEST DEVICES ------------------------

    const DEVICE_1                                           = 'device_1';
    const DEVICE_2                                           = 'device_2';

}
