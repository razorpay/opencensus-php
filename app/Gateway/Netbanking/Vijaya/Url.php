<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class Url
{
    const LIVE_DOMAIN = '';
    const TEST_DOMAIN = 'http://219.65.65.171:9081';

    const AUTHORIZE      = '/NASApp/BANA623WAR/BANKAWAY?Action.ShoppingMall.Login.Init=Y&BankId=029&MD=P&USER_LANG_ID=001&UserType=1&AppType=corporate';

    const VERIFY_BID_ABSENT  = '/NASApp/BANA623WAR/BANKAWAY?Action.ShoppingMall.Login.Init=Y&BankId=029&MD=V&CRN=INR&CG=Y&USER_LANG_ID=001&UserType=1&AppType=corporate';
    const VERIFY_BID_PRESENT = '/EBankingWeb/BANA623WAR/BANKAWAY?Action.ShoppingMall.Login.Init=Y&BankId=029&CRN=INR&MD=V&CG=Y&USER_LANG_ID=001&UserType=1&AppType=corporate';
}
