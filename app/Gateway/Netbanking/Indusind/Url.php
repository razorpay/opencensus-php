<?php

namespace RZP\Gateway\Netbanking\Indusind;

class Url
{
    const LIVE_DOMAIN = 'https://indusnet.indusind.com/corp/BANKAWAY?';
    const TEST_DOMAIN = 'https://indusnetuatfin.indusind.com/corp/BANKAWAY?';

    const AUTHORIZE   = 'Action.ShoppingMall.Login.Init=Y&BankId=234&AppType=corporate&USER_LANG_ID=001';
    const VERIFY      = 'Action.ShoppingMall.Login.Init=Y&BankId=234&AppType=corporate&USER_LANG_ID=001';
}
