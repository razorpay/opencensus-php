<?php

namespace Constants;

class Entity
{
    //
    // Core entities
    //

    const IIN               = 'iin';
    const KEY               = 'key';
    const CARD              = 'card';
    const REFUND            = 'refund';
    const BALANCE           = 'balance';
    const METHODS           = 'merchant_bank';
    const PRICING           = 'pricing';
    const PAYMENT           = 'payment';
    const WEBHOOK           = 'webhook';
    const MERCHANT          = 'merchant';
    const TERMINAL          = 'terminal';
    const ADJUSTMENT        = 'adjustment';
    const SETTLEMENT        = 'settlement';
    const TRANSACTION       = 'transaction';
    const BANK_ACCOUNT      = 'bank_account';
    const DAILY_SETTLEMENT  = 'daily_settlement';

    //
    // Gateway entities
    //
    const ATOM              = 'atom';
    const HDFC              = 'hdfc';
    const AMEX              = 'amex';
    const PAYTM             = 'paytm';
    const BILLDESK          = 'billdesk';
    const MOBIKWIK          = 'mobikwik';
    const AXIS_MIGS         = 'axis_migs';
    const AXIS_GENIUS       = 'axis_genius';
    const NETBANKING_HDFC   = 'netbanking_hdfc';

    public static $core = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
    );

    public static $list = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
        self::ATOM,
        self::HDFC,
        self::AMEX,
        self::PAYTM,
        self::BILLDESK,
        self::MOBIKWIK,
        self::AXIS_MIGS,
        self::AXIS_GENIUS,
        self::NETBANKING_HDFC,
    );

    public static $map = array(
        self::IIN,
        self::KEY,
        self::CARD,
        self::REFUND,
        self::BALANCE,
        self::METHODS,
        self::PRICING,
        self::PAYMENT,
        self::WEBHOOK,
        self::MERCHANT,
        self::TERMINAL,
        self::ADJUSTMENT,
        self::SETTLEMENT,
        self::TRANSACTION,
        self::BANK_ACCOUNT,
        self::DAILY_SETTLEMENT,
    );

    /**
     * Entities exposed outside
     * @var array
     */
    public static $public = array(
        self::PAYMENT,
        self::REFUND,
    );
}