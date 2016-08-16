<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const IIN                       = 'iins';
    const KEY                       = 'keys';
    const CARD                      = 'cards';
    const ORDER                     = 'orders';
    const TOKEN                     = 'tokens';
    const REFUND                    = 'refunds';
    const INVOICE                   = 'invoices';
    const BALANCE                   = 'balance';
    const METHODS                   = 'merchant_banks';
    const PRICING                   = 'pricing';
    const PAYMENT                   = 'payments';
    const WEBHOOK                   = 'webhooks';
    const MERCHANT                  = 'merchants';
    const TERMINAL                  = 'terminals';
    const CUSTOMER                  = 'customers';
    const EMI_PLAN                  = 'emi_plans';
    const LINE_ITEM                 = 'line_items';
    const APP_TOKEN                 = 'customer_apps';
    const ADJUSTMENT                = 'adjustment';
    const SETTLEMENT                = 'settlements';
    const TRANSACTION               = 'transactions';
    const BANK_ACCOUNT              = 'bank_accounts';
    const INVOICE_ITEM              = 'invoice_items';
    const DAILY_SETTLEMENT          = 'daily_settlements';
    const SETTLEMENT_DETAIL         = 'settlement_details';

    // Gateway related
    const ATOM                      = 'atom';
    const HDFC                      = 'hdfc';
    const AXIS                      = 'axis';
    const CYBERSOURCE               = 'cybersource';
    const PAYTM                     = 'paytm';
    const BILLDESK                  = 'billdesk';
    const MOBIKWIK                  = 'mobikwik';
    const NETBANKING                = 'netbanking';

    // Sessions table
    const SESSION                   = 'sessions';

    // Internal Purposes
    const CREDITS                   = 'credits';

    // Terminal Performance
    const TERMINAL_ACTION           = 'terminal_action_logs';
    // TODO: Confirm with vv if it's okay to change the table now at this point of time.
    const GATEWAY_STATUS_ABSENCE    = 'gatewaystatus_absence';

    // Payment Analytics
    const PAYMENT_ANALYTICS         = 'payment_analytics';
}
