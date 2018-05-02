<?php

namespace RZP\Gateway\Netbanking\Obc;

class Url
{
    const TEST_DOMAIN = 'http://220.226.198.27/PreProdcorp';

    const LIVE_DOMAIN = 'https://www.obconline.co.in/corp';

    const AUTHORIZE   = '/AuthenticationController?FORMSGROUP_ID__=AuthenticationFG&__START_TRAN_FLAG__=Y&FG_BUTTONS__=LOAD&ACTION.LOAD=Y&AuthenticationFG.LOGIN_FLAG=1&BANK_ID=022&AuthenticationFG.USER_TYPE=1&AuthenticationFG.MENU_ID=CIMSHP&AuthenticationFG.CALL_MODE=2';

    const VERIFY      = '/VService?Action.ShoppingMall.Login.Init=Y&BankId=022&UserType=1&USER_LANG_ID=001&AppType=corporate&MD=V&CG=Y&STATFLG=H&CRN=INR';
}
