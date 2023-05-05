<?php

namespace RZP\Gateway\Upi\Yesbank;

class Url
{
    const LIVE_DOMAIN          = 'https://skyway.yesbank.in:443/app/live/upi/';
    const TEST_DOMAIN          = 'https://uatskyway.yesbank.in:443/app/uat/upi/';

    const PAYOUT               = 'mePayServerReqImp';
    const PAYOUT_VERIFY        = 'meTransStatusQuery';
}
