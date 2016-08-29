<?php

namespace RZP\Gateway\UPI\ICICI;

class Url
{
    const TEST_DOMAIN   = 'https://apigwuat.icicibank.com:8443';
    const AUTHORIZE     = '/newCollectPay';
    const VERIFY        = '/transactionStatus';
}
