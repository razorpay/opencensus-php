<?php

namespace Constants;

class Entity
{
    //
    // Core entities
    //

    const IIN               = 'iins';
    const KEY               = 'keys';
    const CARD              = 'cards';
    const REFUND            = 'refunds';
    const BALANCE           = 'balance';
    const METHODS           = 'merchant_banks';
    const PRICING           = 'pricing';
    const PAYMENT           = 'payments';
    const WEBHOOK           = 'webhooks';
    const MERCHANT          = 'merchants';
    const TERMINAL          = 'terminals';
    const ADJUSTMENT        = 'adjustment';
    const SETTLEMENT        = 'settlements';
    const TRANSACTION       = 'transactions';
    const BANK_ACCOUNT      = 'bank_accounts';
    const DAILY_SETTLEMENT  = 'daily_settlements';

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