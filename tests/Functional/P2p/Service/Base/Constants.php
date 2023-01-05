<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\Merchant\Account;

class Constants
{
    // ------------------- MERCHANT -------------------

    const TEST_MERCHANT                                      = Account::TEST_ACCOUNT;
    const DEMO_MERCHANT                                      = Account::DEMO_ACCOUNT;
    const SHARED_MERCHANT                                    = Account::SHARED_ACCOUNT;

    // --------------------- BANKS ---------------------

    const ARZP                                              = 'ARZP0000000001';
    const BRZP                                              = 'BRZP0000000001';
    const CRZP                                              = 'CRZP0000000001';

    const ARZP_AXIS                                         = 'ARZP0000000002';
    const BRZP_AXIS                                         = 'BRZP0000000002';
    const CRZP_AXIS                                         = 'CRZP0000000002';

    // ------------------------ TEST DEVICES ------------------------

    const DEVICE_1                                           = 'device_1';
    const DEVICE_2                                           = 'device_2';

    // ------------------- CUSTOMERS -------------------

    const RZP_LOCAL_CUSTOMER_1                               = 'ArzpLocalCust1';
    const RZP_LOCAL_CUSTOMER_2                               = 'ArzpLocalCust2';

    // ------------------- DEVICES ---------------------

    const CUSTOMER_1_DEVICE_1                                = 'ALC01device001';
    const CUSTOMER_2_DEVICE_1                                = 'ALC02device001';

    // -------------------- GATEWAY --------------------

    const P2P_UPI_SHARP                                     = 'p2p_upi_sharp';
    const P2P_UPI_AXIS                                      = 'p2p_upi_axis';
    const P2M_UPI_AXIS_OLIVE                                = 'p2m_upi_axis_olive';

    // ------------------ DEVICE TOKEN ------------------

    const CUSTOMER_1_DEVICE_TOKEN_1_SHARP                    = 'ALC01DevTok001';
    const CUSTOMER_1_DEVICE_TOKEN_2_SHARP                    = 'ALC01DevTok002';

    const CUSTOMER_2_DEVICE_TOKEN_1_SHARP                    = 'ALC02DevTok001';
    const CUSTOMER_2_DEVICE_TOKEN_2_SHARP                    = 'ALC02DevTok002';

    const CUSTOMER_1_DEVICE_TOKEN_1_AXIS                     = 'ALC01DevTok003';
    const CUSTOMER_1_DEVICE_TOKEN_2_AXIS                     = 'ALC01DevTok004';

    const CUSTOMER_2_DEVICE_TOKEN_1_AXIS                     = 'ALC02DevTok003';
    const CUSTOMER_2_DEVICE_TOKEN_2_AXIS                     = 'ALC02DevTok004';

    // --------------- BANK ACCOUNT -----------------------

    const CUSTOMER_1_BANK_ACCOUNT_1_SHARP                    = 'ALC01bankAc001';
    const CUSTOMER_2_BANK_ACCOUNT_1_SHARP                    = 'ALC02bankAc001';

    const CUSTOMER_1_BANK_ACCOUNT_1_AXIS                     = 'ALC01bankAc002';
    const CUSTOMER_2_BANK_ACCOUNT_1_AXIS                     = 'ALC02bankAc002';

    // ----------------- VPA ------------------------------

    const CUSTOMER_1_VPA_1_SHARP                             = 'ALC01custVpa01';
    const CUSTOMER_1_VPA_2_SHARP                             = 'ALC01custVpa02';
    const CUSTOMER_2_VPA_1_SHARP                             = 'ALC02custVpa01';
    const CUSTOMER_2_VPA_2_SHARP                             = 'ALC02custVpa02';

    const CUSTOMER_1_VPA_1_AXIS                              = 'ALC01custVpa03';
    const CUSTOMER_1_VPA_2_AXIS                              = 'ALC01custVpa04';
    const CUSTOMER_2_VPA_1_AXIS                              = 'ALC02custVpa03';
    const CUSTOMER_2_VPA_2_AXIS                              = 'ALC02custVpa04';

    // -------------------- HANDLES  -------------------

    const RAZOR_SHARP                                       = 'razorsharp';
    const RZP_SHARP                                         = 'rzpsharp';
    const NORZP_SHARP                                       = 'norzpsharp';

    const RAZOR_AXIS                                        = 'razoraxis';
    const RZP_AXIS                                          = 'rzpaxis';
    const NORZP_AXIS                                        = 'norzpaxis';

    const RAZOR_AXIS_OLIVE                                  = 'razoraxisolive';
    const RZP_AXIS_OLIVE                                    = 'rzpaxisolive';
    const NORZP_AXIS_OLIVE                                  = 'norzpaxisolive';

    // Clients
    const CLIENT_1_RAZORAXIS_MER1                           = 'CL01TestRzAxis';
    const CLIENT_2_RAZORAXIS_MER2                           = 'CL02DemoRzAxis';
    const CLIENT_1_RAZORSHARP_MER1                          = 'CL01ShrdRzShrp';

    const CLIENT_3_RAZORSHARP_MER1                          = 'CL03TestRzAxis';
    const CLIENT_4_RAZORSHARP_MER1                          = 'CL04TestRzAxis';
}
