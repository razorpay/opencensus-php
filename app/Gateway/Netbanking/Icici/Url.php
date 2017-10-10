<?php

namespace RZP\Gateway\Netbanking\Icici;

class Url
{
    const RETAIL_LIVE_DOMAIN    = 'https://shopping.icicibank.com/corp/BANKAWAY?';
    const RETAIL_TEST_DOMAIN    = 'https://shopping.icicibank.com/corp/BANKAWAY?';

    const RECURRING_LIVE_DOMAIN = 'https://shopping.icicibank.com/corp/BANKAWAY?';
    const RECURRING_TEST_DOMAIN = 'https://shopping.icicibank.com/corp/BANKAWAY?';

    const CORPORATE_LIVE_DOMAIN = 'https://cibnext.icicibank.com/corp/BANKAWAY?';
    const CORPORATE_TEST_DOMAIN = 'https://cibnextcug.icicibank.com/corp/BANKAWAY?';

    const RETAIL_QUERY          = 'IWQRYTASKOBJNAME=bay_mc_login&BAY_BANKID=ICI';

    const RECURRING_QUERY       = 'IWQRYTASKOBJNAME=bay_mc_login&BAY_BANKID=ICI';

    const CORPORATE_QUERY       = 'Action.ShoppingMall.Login.Init=Y&BankId=ICI&USER_LANG_ID=001&AppType=corporate&UserType=2';
}
