<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Models\Key;

class Account
{
    const NODAL_ACCOUNT         = '10NodalAccount';
    const ATOM_ACCOUNT          = '100AtomAccount';

    const API_FEE_ACCOUNT       = '1ApiFeeAccount';

    const DEMO_ACCOUNT          = '100DemoAccount';
    const TEST_ACCOUNT          = '10000000000000';
    const SHARED_ACCOUNT        = '100000Razorpay';

    const DEMO_PAGE_ACCOUNT     = '2aTeFCKTYWwfrF';

    const TEST_ACCOUNT_2        = '4izmfM9TFCAgFN';

    const TEST_ACCOUNT_KEY_ID   = '1DP5mmOlF5G5ag';
    const DEMO_ACCOUNT_KEY_ID   = '0wFRWIZnH65uny';

    const DEMO_VA_TEST          = '5ubLZpACTmD8D4';

    const MEDLIFE               = '6knz9sdyiFESCn';
    const OKCREDIT              = 'BhxjLIZbVWc0AI';

    const X_DEMO_PROD_ACCOUNT        = 'Hy5Vxj9TTVm4Oi';
    const X_DEMO_BETA_ACCOUNT        = 'Hrw2ujXW6LGEk7';

    const FUND_LOADING_DOWNTIME_DETECTION_SOURCE_ACCOUNT_MID = 'JX04vtuLFZyc8P';
    const FUND_LOADING_DOWNTIME_DETECTION_DESTINATION_ACCOUNT_MID = 'JXR5VxmNDmWy1z';

    protected static $nodalAccounts = [
        self::NODAL_ACCOUNT,
        self::ATOM_ACCOUNT
    ];

    protected static $testAccounts = [
        self::DEMO_ACCOUNT,
        self::TEST_ACCOUNT,
    ];

    protected static $xDemoAccounts = [
        self::X_DEMO_PROD_ACCOUNT,
        self::X_DEMO_BETA_ACCOUNT
    ];

    /**
     * This is list of merchants which are used in automation suite. Private
     * credentials of these merchant are with automation suite allowing later to
     * make api calls etc. Automation is added as a application for api service i.e.
     * automation can do white-listed proxy/internal api call as well. Automation
     * service besides this much wants to restrict its accesses to a set of merchant ids
     * which it works with- for reasons. This list is used in BasicAuth for the same.
     */
    const AUTOMATION_SUITE_MERCHANT_IDS = [
        '9kmnB8D4KxfyQP',
        '94tLpgbojcR85O',
    ];

    public static function isNodalAccount($id)
    {
        return in_array($id, self::$nodalAccounts);
    }

    public static function isTestAccount($id)
    {
        return in_array($id, self::$testAccounts);
    }

    public static function isXDemoAccount($id): bool
    {
        return in_array($id, self::$xDemoAccounts,true);
    }
}
