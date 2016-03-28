<?php

namespace Gateway\Wallet\Payumoney;

class Url
{
    const LIVE_DOMAIN	= 'https://payumoney.com';
    const TEST_DOMAIN	= 'https://test.payumoney.com';

    const REFUND		= '/payment/merchant/refundPayment';

    const AUTHORIZE		= '/payment/ext/wallet/useWallet';
    const LOAD_WALLET	= '/payment/ext/wallet/loadWalletPayment';

    const VERIFY		= '/vault/ext/getTransactionStatus';

    const REGISTER_USER	= '/auth/ext/wallet/register';
    const OTP_SUBMIT	= '/auth/ext/wallet/verify';
    const GET_BALANCE	= '/auth/ext/wallet/getWalletLimit';
}