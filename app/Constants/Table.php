<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const IIN                   = 'iins';
    const KEY                   = 'keys';
    const CARD                  = 'cards';
    const BATCH                 = 'batches';
    const ORDER                 = 'orders';
    const TOKEN                 = 'tokens';
    const REFUND                = 'refunds';
    const BALANCE               = 'balance';
    const METHODS               = 'merchant_banks';
    const PRICING               = 'pricing';
    const SCHEDULE              = 'schedules';
    const PAYMENT               = 'payments';
    const WEBHOOK               = 'webhooks';
    const ADDRESS               = 'addresses';
    const MERCHANT              = 'merchants';
    const FEATURE               = 'features';
    const TERMINAL              = 'terminals';
    const CUSTOMER              = 'customers';
    const FEE_BREAKUP           = 'fees_breakup';
    const EMI_PLAN              = 'emi_plans';
    const APP_TOKEN             = 'customer_apps';
    const FILESTORE             = 'files';
    const ADJUSTMENT            = 'adjustment';
    const SETTLEMENT            = 'settlements';
    const TRANSACTION           = 'transactions';
    const BANK_ACCOUNT          = 'bank_accounts';
    const DAILY_SETTLEMENT      = 'daily_settlements';
    const SETTLEMENT_DETAILS    = 'settlement_details';

    // Gateway related
    const EBS                   = 'ebs';
    const ATOM                  = 'atom';
    const HDFC                  = 'hdfc';
    const MIGS                  = 'axis';
    const CYBERSOURCE           = 'cybersource';
    const FIRST_DATA            = 'first_data';
    const PAYTM                 = 'paytm';
    const BILLDESK              = 'billdesk';
    const MOBIKWIK              = 'mobikwik';
    const NETBANKING            = 'netbanking';
    const UPI                   = 'upi';
    const WALLET                = 'wallet';

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
