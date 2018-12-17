<?php

namespace RZP\Gateway\Upi\Yesbank;

class Url
{
    const LIVE_DOMAIN          = 'https://sky.yesbank.in:444/app/live/upi/';
    const TEST_DOMAIN          = 'https://uatsky.yesbank.in:444/app/uat/upi/';

    const PAYOUT               = 'mePayServerReqImp';
    const PAYOUT_VERIFY        = 'meTransStatusQuery';
}
