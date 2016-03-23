<?php

namespace Constants;

class Table
{
    // Core entities

    const IIN               = 'iins';
    const KEY               = 'keys';
    const CARD              = 'cards';
    const ORDER             = 'orders';
    const REFUND            = 'refunds';
    const BALANCE           = 'balance';
    const METHODS           = 'merchant_banks';
    const PRICING           = 'pricing';
    const PAYMENT           = 'payments';
    const WEBHOOK           = 'webhooks';
    const MERCHANT          = 'merchants';
    const TERMINAL          = 'terminals';
    const CUSTOMER          = 'customers';
    const ADJUSTMENT        = 'adjustment';
    const SETTLEMENT        = 'settlements';
    const EMI_PLAN          = 'emi_plans';
    const TRANSACTION       = 'transactions';
    const BANK_ACCOUNT      = 'bank_accounts';
    const CUSTOMER_APP      = 'customer_apps';
    const CUSTOMER_METHOD   = 'customer_methods';
    const DAILY_SETTLEMENT  = 'daily_settlements';

    // Gateway related
    const ATOM              = 'atom';
    const HDFC              = 'hdfc';
    const AXIS              = 'axis';
    const PAYTM             = 'paytm';
    const BILLDESK          = 'billdesk';
    const MOBIKWIK          = 'mobikwik';
    const NETBANKING        = 'netbanking';
}
