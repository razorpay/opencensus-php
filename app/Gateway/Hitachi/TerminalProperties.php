<?php

namespace RZP\Gateway\Hitachi;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class TerminalProperties
{
    const COUNTRY           = 'IN';

    const PROCESS_ID        = 'RAZORPAY';

    const CUSTOMER_NO       = '';

    const EXISITNG_MERCHANT = 'N';

    const SPONSER_BANK      = '38RAZOR';

    const ACTION_CODE       = 'A';

    const SUPER_MID         = 'RZTEST000000001';

    const MERCHANT_STATUS   = 'E';

    const BANK              = 'RAZORPAY RBL';

    const INTERNATIONAL     = 'Y';

    const TERMINAL_ACTIVE   = 'Y';

    public function getCountry()
    {
        return self::COUNTRY;
    }

    public function getProcessId()
    {
        return self::PROCESS_ID;
    }

    public function getCustomerNo()
    {
        return self::CUSTOMER_NO;
    }

    public function getExistingMerchant()
    {
        return self::EXISITNG_MERCHANT;
    }

    public function getSponserBank()
    {
        return self::SPONSER_BANK;
    }

    public function getActionCode()
    {
        return self::ACTION_CODE;
    }

    public function getSno()
    {

        // need to be unique, currently unique per second
        return (string) Carbon::now(Timezone::IST)->timestamp;

    }

    public function getSuperMid()
    {
        return self::SUPER_MID;
    }

    public function getMerchantStatus()
    {
        return self::MERCHANT_STATUS;
    }

    public function getBank()
    {
        return self::BANK;
    }

    public function getInternational()
    {
        return self::INTERNATIONAL;
    }

    public function getTerminalActive()
    {
        return self::TERMINAL_ACTIVE;
    }
}
