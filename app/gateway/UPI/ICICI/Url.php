<?php

namespace Gateway\UPI\ICICI;

class Url
{
    const BASE = 'https://imob.icicibank.com/isdkCUG/GatewayController'

    const COLLECT_PAY = 'https://apigwuat.icicibank.com:8443/newCollectPay'

    const STATUS = 'https://apigwuat.icicibank.com:8443/newTransactionStatus';
}
