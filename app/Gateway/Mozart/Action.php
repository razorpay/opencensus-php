<?php

namespace RZP\Gateway\Mozart;

class Action
{
    const CHECKACCOUNT          = 'check_account';
    const PAY_INIT              = 'pay_init';
    const PAY_VERIFY            = 'pay_verify';
    const AUTH_INIT             = 'auth_init';
    const AUTH_VERIFY           = 'auth_verify';
    const AUTHENTICATE_INIT     = 'authenticate_init';
    const AUTHENTICATE_VERIFY   = 'authenticate_verify';
    const CHECK_BALANCE         = 'check_balance';

    const CAPTURE               = 'capture';
    const REFUND                = 'refund';
    const VERIFY                = 'verify';
    const VERIFY_REFUND         = 'verify_refund';

    const DEBIT                 = 'debit';

    const AUTHORIZE             = 'authorize';

    const OMNI_PAY              = 'omni_pay';

    const INTENT                = 'intent';

    //action to fetch reconcile data in case of api based recon
    const RECONCILE             = 'reconcile';

    const CREATE_TERMINAL       = 'create_terminal';

    const DISABLE_TERMINAL      = 'disable_terminal';

    const ENABLE_TERMINAL       = 'enable_terminal';

    const MANDATE_CREATE        = 'mandate_create';

    const MANDATE_EXECUTE       = 'mandate_execute';

    const MANDATE_UPDATE        = 'mandate_update';

    const MANDATE_UPDATE_VERIFY = 'mandate_update_verify';

    const MANDATE_CREATE_VERIFY = 'mandate_create_verify';

    const DECRYPT               = 'decrypt';

    const MERCHANT_ONBOARD      = 'merchantOnboard';

    const MANDATE_REVOKE        = 'mandate_revoke';

    const NOTIFY                = 'notify';

    const VALIDATE              = 'validate';

    const PRE_PROCESS           = 'pre_process';

    const CALLBACK_DECRYPTION   = 'callback_decryption';

    const CREATE_VIRTUAL_ACCOUNT = 'create_virtual_account';

    const UPDATE_TOKEN          = 'update_token';

    const CHECK_BIN             = 'check_bin';
}
