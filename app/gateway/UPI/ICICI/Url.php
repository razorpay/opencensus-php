<?php

namespace Gateway\UPI\ICICI;

class Url
{
    const BASE_TEST_URL = 'https://apigwuat.icicibank.com:8443';

    const AUTHORIZE     = '/newCollectPay';
    const STATUS        = '/newTransactionStatus';
}
