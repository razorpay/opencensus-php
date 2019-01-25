<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class Url
{
    const LIVE_DOMAIN = 'https://www.vijayabankonline.in';
    const TEST_DOMAIN = 'http://219.65.65.171:9081';

    const AUTHORIZE = '/NASApp/BANA623WAR/BANKAWAY?Action.ShoppingMall.Login.Init=Y&BankId=029&MD=P&USER_LANG_ID=001&UserType=1&AppType=corporate';

    const VERIFY = '/NASApp/BANA623WAR/BANKAWAY?Action.ShoppingMall.Login.Init=Y&BankId=029&MD=V&CRN=INR&CG=Y&USER_LANG_ID=001&UserType=1&AppType=corporate';
}