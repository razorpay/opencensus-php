<?php

namespace RZP\Gateway\Wallet\Payumoney;

class Url
{
    const LIVE_DOMAIN    = 'https://www.payumoney.com';
    const TEST_DOMAIN    = 'https://test.payumoney.com';

    const REFUND         = '/payment/merchant/refundPayment';

    const DEBIT_WALLET   = '/payment/ext/wallet/useWallet';
    const TOPUP_WALLET   = '/payment/ext/wallet/loadWalletPayment';
    const TOPUP_REDIRECT = '/payment/payment/extTxn';

    const VERIFY         = '/vault/ext/getTransactionStatus';

    const OTP_GENERATE   = '/auth/ext/wallet/register';
    const OTP_SUBMIT     = '/auth/ext/wallet/verify';
    const GET_BALANCE    = '/auth/ext/wallet/getWalletLimit';
}
