<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const IIN               = 'iins';
    const KEY               = 'keys';
    const CARD              = 'cards';
    const ORDER             = 'orders';
    const TOKEN             = 'tokens';
    const REFUND            = 'refunds';
    const BALANCE           = 'balance';
    const METHODS           = 'merchant_banks';
    const PRICING           = 'pricing';
    const PAYMENT           = 'payments';
    const WEBHOOK           = 'webhooks';
    const MERCHANT          = 'merchants';
    const TERMINAL          = 'terminals';
    const CUSTOMER          = 'customers';
    const APP_TOKEN         = 'customer_apps';
    const ADJUSTMENT        = 'adjustment';
    const SETTLEMENT        = 'settlements';
    const EMI_PLAN          = 'emi_plans';
    const TRANSACTION       = 'transactions';
    const BANK_ACCOUNT      = 'bank_accounts';
    const DAILY_SETTLEMENT  = 'daily_settlements';
    const SETTLEMENT_DETAIL = 'settlement_details';

    // Gateway related
    const ATOM              = 'atom';
    const HDFC              = 'hdfc';
    const AXIS              = 'axis';
    const CYBERSOURCE       = 'cybersource';
    const PAYTM             = 'paytm';
    const BILLDESK          = 'billdesk';
    const MOBIKWIK          = 'mobikwik';
    const NETBANKING        = 'netbanking';

    // Sessions table
    const SESSION           = 'sessions';
    
    // Terminal Performance
    const TERMINAL_ACTION    = 'terminal_action_logs';
    const TERMINAL_ABSENCE  = 'terminal_absence_schedule';

    // Payment Analytics
    const PAYMENT_ANALYTICS    = 'payment_analytics';
}
