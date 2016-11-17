<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const IIN                   = 'iins';
    const KEY                   = 'keys';
    const CARD                  = 'cards';
    const ITEM                  = 'items';
    const BATCH                 = 'batches';
    const ORDER                 = 'orders';
    const TOKEN                 = 'tokens';
    const REFUND                = 'refunds';
    const INVOICE               = 'invoices';
    const BALANCE               = 'balance';
    const METHODS               = 'merchant_banks';
    const PRICING               = 'pricing';
    const PAYMENT               = 'payments';
    const WEBHOOK               = 'webhooks';
    const ADDRESS               = 'addresses';
    const FEATURE               = 'features';
    const TERMINAL              = 'terminals';
    const MERCHANT              = 'merchants';
    const CUSTOMER              = 'customers';
    const SCHEDULE              = 'schedules';
    const EMI_PLAN              = 'emi_plans';
    const LINE_ITEM             = 'line_items';
    const APP_TOKEN             = 'customer_apps';
    const FILE_STORE            = 'files';
    const ADJUSTMENT            = 'adjustment';
    const SETTLEMENT            = 'settlements';
    const FEE_BREAKUP           = 'fees_breakup';
    const TRANSACTION           = 'transactions';
    const BANK_ACCOUNT          = 'bank_accounts';
    const DAILY_SETTLEMENT      = 'daily_settlements';
    const SETTLEMENT_DETAILS    = 'settlement_details';

    // Gateway related
    const EBS                   = 'ebs';
    const UPI                   = 'upi';
    const ATOM                  = 'atom';
    const HDFC                  = 'hdfc';
    const MIGS                  = 'axis';
    const PAYTM                 = 'paytm';
    const WALLET                = 'wallet';
    const BILLDESK              = 'billdesk';
    const MOBIKWIK              = 'mobikwik';
    const NETBANKING            = 'netbanking';
    const FIRST_DATA            = 'first_data';
    const CYBERSOURCE           = 'cybersource';

    // Sessions table
    const SESSION               = 'sessions';

    // Internal Purposes
    const CREDITS               = 'credits';

    // Terminal Performance

    const TERMINAL_ACTION       = 'terminal_action_logs';
    const GATEWAY_ABSENCE       = 'gateway_status_absence';

    // Payment Analytics
    const PAYMENT_ANALYTICS     = 'payment_analytics';
    const TERMINAL_ANALYTICS    = 'terminal_analytics';

    protected static $entityToTableMap = array(
        Entity::AXIS_MIGS       => self::MIGS,
        Entity::AXIS_GENIUS     => self::MIGS,
        Entity::AMEX            => self::MIGS,
    );

    public static function getTableNameForEntity(string $entity)
    {
        Entity::validateEntityOrFail($entity);

        if (isset(self::$entityToTableMap[$entity]))
        {
            return self::$entityToTableMap[$entity];
        }

        return constant(Table::class.'::'.strtoupper($entity));
    }
}
