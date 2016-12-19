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

    const TEST_ACCOUNT_2        = '4izmfM9TFCAgFN';

    const TEST_ACCOUNT_KEY_ID   = '1DP5mmOlF5G5ag';
    const DEMO_ACCOUNT_KEY_ID   = '0wFRWIZnH65uny';

    protected static $nodalAccounts = array(
        self::NODAL_ACCOUNT,
        self::ATOM_ACCOUNT);

    public static function isNodalAccount($id)
    {
        return in_array($id, self::$nodalAccounts);
    }

}