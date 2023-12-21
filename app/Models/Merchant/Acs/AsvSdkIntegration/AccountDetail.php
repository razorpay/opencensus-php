<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use Accounts\Account\V1 as AccountV1;

class AccountDetail extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setAccountDetails($accountDetails) : AccountV1\AccountDetail
    {
        $accountDetail = new AccountV1\AccountDetail();

        $accountDetail->setEddVerificationStatusUnwrapped($accountDetails['edd_verification_status']);

        return $accountDetail;

    }
}
