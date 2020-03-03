<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

class Url
{
    const LIVE_DOMAIN = 'https://enach.npci.org.in';
    const TEST_DOMAIN = 'https://103.14.161.144:8086';

    const NPCIAUTH     = '/onmags_new/sendRequest';
    const NPCIAUTH_OLD = '/onmags/sendRequest';
    const VERIFY       = '/apiservices/getTransStatusForMerchant';
}
