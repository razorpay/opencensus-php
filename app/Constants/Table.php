<?php

namespace RZP\Constants;

class Table
{
    // Core entities
    const P2P                   = 'p2p';
    const VPA                   = 'vpa';
    const IIN                   = 'iins';
    const KEY                   = 'keys';
    const RUN                   = 'runs';
    const CARD                  = 'cards';
    const PLAN                  = 'plans';
    const ITEM                  = 'items';
    const BATCH                 = 'batches';
    const ORDER                 = 'orders';
    const TOKEN                 = 'tokens';
    const PAYOUT                = 'payouts';
    const REFUND                = 'refunds';
    const INVOICE               = 'invoices';
    const BALANCE               = 'balance';
    const METHODS               = 'merchant_banks';
    const PRICING               = 'pricing';
    const SCHEDULE              = 'schedules';
    const DEVICE                = 'devices';
    const UPI_VPA               = 'upi_vpa';
    const PAYMENT               = 'payments';
    const WEBHOOK               = 'webhooks';
    const ADDRESS               = 'addresses';
    const MERCHANT              = 'merchants';
    const EMI_PLAN              = 'emi_plans';
    const FEATURE               = 'features';
    const TERMINAL              = 'terminals';
    const CUSTOMER              = 'customers';
    const TRANSFER              = 'transfers';
    const REVERSAL              = 'reversals';
    const LINE_ITEM             = 'line_items';
    const APP_TOKEN             = 'customer_apps';
    const FILE_STORE            = 'files';
    const ADJUSTMENT            = 'adjustment';
    const SETTLEMENT            = 'settlements';
    const FEE_BREAKUP           = 'fees_breakup';
    const TRANSACTION           = 'transactions';
    const BANK_ACCOUNT          = 'bank_accounts';
    const SUBSCRIPTION          = 'subscriptions';
    const CUSTOMER_BALANCE      = 'customer_balance';
    const CUSTOMER_TRANSACTION  = 'customer_transactions';
    const BATCH_SETTLEMENT      = 'daily_settlements';
    const SETTLEMENT_DETAILS    = 'settlement_details';
    const MERCHANT_DETAIL       = 'merchant_details';
    const MERCHANT_TERMINAL     = 'merchant_terminal';
    const OFFER                 = 'offers';
    const COUPON                = 'coupons';
    const MERCHANT_OFFER        = 'merchant_offer';

    // organization roles permissions
    const ORG                   = 'orgs';
    const ORG_HOSTNAME          = 'org_hostname';
    const ROLE                  = 'roles';
    const PERMISSION            = 'permissions';
    const PERMISSION_MAP        = 'permission_map';
    const GROUP                 = 'groups';
    const ADMIN                 = 'admins';
    const GROUP_MAP             = 'group_map';
    const MERCHANT_MAP          = 'merchant_map';
    const ADMIN_TOKEN           = 'admin_tokens';
    const ROLE_MAP              = 'role_map';
    const LOGIN_ATTEMPT         = 'login_attempts';

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
        Entity::AXIS_MIGS           => self::MIGS,
        Entity::AXIS_GENIUS         => self::MIGS,
        Entity::AMEX                => self::MIGS,
        Entity::WALLET_FREECHARGE   => self::WALLET,
        Entity::WALLET_OLAMONEY     => self::WALLET,
        Entity::WALLET_AIRTELMONEY  => self::WALLET,
        Entity::WALLET_PAYUMONEY    => self::WALLET,
    );

    public static function getTableNameForEntity(string $entity)
    {
        Entity::validateEntityOrFail($entity);

        if (isset(self::$entityToTableMap[$entity]))
        {
            return self::$entityToTableMap[$entity];
        }

        return constant(Table::class . '::' . strtoupper($entity));
    }
}
