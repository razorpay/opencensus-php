<?php

namespace RZP\Gateway\Netbanking\Rbl;

class Url
{
	const LIVE_DOMAIN   = 'https://www.fednetbank.com';
    const TEST_DOMAIN   = 'https://onlineuat.rblbank.com/corp/AuthenticationController?';

    const AUTHORIZE     = 'FORMSGROUP_ID__=AuthenticationFG&__START_TRAN_FLAG__=Y&FG_BUTTONS__=LOAD&ACTION.LOAD=Y&AuthenticationFG.LOGIN_FLAG=1&BANK_ID=176';
    const VERIFY        = '/Verify';
}
