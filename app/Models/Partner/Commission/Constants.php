<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Status as DetailStatus;

class Constants
{
    const PAYMENT = 'payment';
    const PAYOUT =  'payout';

    const COMMISSION_BREAK_UP_PREFIX = 'commission_';

    /**
     * Activated submerchant count needed to show commission aggregate report for reseller
     */
    const RESELLER_SUBMERCHANT_LIMIT = 3;

    // analytics constants
    const TO                  = 'to';
    const FROM                = 'from';
    const QUERY_TYPE          = 'query_type';
    const AGGREGATE_DAILY     = 'aggregate_daily'; // day wise commission aggregates
    const AGGREGATE_DETAIL    = 'aggregate_detail'; // aggregate break up details for given day
    const SUBVENTION_DAILY    = 'subvention_daily'; // day wise subvention aggregates
    const SUBVENTION_DETAIL   = 'subvention_detail'; // subvention break up details for given day

    const TOTAL_COMMISSION = 'total_commission';
    const TOTAL_TAX        = 'total_tax';
    const TOTAL_TDS        = 'total_tds';
    const TOTAL_NET_AMOUNT = 'total_net_amount';
    const COMPONENTS       = 'components';
    const TDS              = 'tds';
    const TDS_PERCENTAGE   = 'tds_percentage';

    const FIXED    = 'fixed';
    const VARIABLE = 'variable';

    const ADJUSTMENT_TDS_DESCRIPTION = 'Tds deduction on commission payout';

    // line item names
    const COMMISSION = 'commission';
    const ADJUSTMENT = 'adjustment';

    const PRIMARY_COMMISSION = 'primary_commission';
    const BANKING_COMMISSION = 'banking_commission';

    //sms templates for Partner activation status
    const PARTNER_ACTIVATED_TEMPLATE               = 'Partner_commission_invoice.Activated';
    const PARTNER_UNDER_REVIEW_TEMPLATE            = 'Partner_commission_invoice.Under_review';
    const PARTNER_NEEDS_CLARIFICATION_TEMPLATE     = 'Partner_commission_invoice.Needs_clarification';
    const PARTNER_DEFAULT_TEMPLATE                 = 'Partner_commission_invoice.Null';
    const PARTNER_INVOICE_AUTO_APPROVED_TEMPLATE   = 'Partner_commission_invoice.Auto_Approved';

    const COMMISSION_COMPUTED_ZERO_EVENT_NAME      =  'commission_computed_zero';
    const COMMISSION_COMPUTED_NEGATIVE_EVENT_NAME  =  'commission_computed_negative';

    const COMMISSIONS_EVENTS_TOPIC        =  'events.commission-events.v1.';
    const COMMISSION_EVENTS               =  'commission-events';
    const COMMISSION_EVENTS_VERSION       =  'v1';
    const INVOICE_AUTO_APPROVED           = 'invoice_auto_approved';

    const COMMISSION_SYNC_OUTBOX_JOB      = 'partnerships.commission_sync.v1';

    const VALID_PARTNER_STATUS_EMAIL_TEMPLATES = [
        DetailStatus::ACTIVATED,
        DetailStatus::NEEDS_CLARIFICATION,
        DetailStatus::UNDER_REVIEW,
        DetailStatus::REJECTED,
    ];

    const COMMISSION_INVOICE_ISSUED_SMS_TEMPLATE = [
        self::INVOICE_AUTO_APPROVED            => self::PARTNER_INVOICE_AUTO_APPROVED_TEMPLATE,
        DetailStatus::ACTIVATED                => self::PARTNER_ACTIVATED_TEMPLATE,
        DetailStatus::UNDER_REVIEW             => self::PARTNER_UNDER_REVIEW_TEMPLATE,
        DetailStatus::NEEDS_CLARIFICATION      => self::PARTNER_NEEDS_CLARIFICATION_TEMPLATE,
        Merchant\Constants::DEFAULT            => self::PARTNER_DEFAULT_TEMPLATE,
    ];

    //sms templates for commission reminders
    const PARTNER_ACTIVATED_REMINDER_SMS_TEMPLATE               = 'Partner_commission_invoice_reminder.Activated';
    const PARTNER_UNDER_REVIEW_REMINDER_SMS_TEMPLATE            = 'Partner_commission_invoice_reminder.Under_review';
    const PARTNER_NEEDS_CLARIFICATION_REMINDER_SMS_TEMPLATE     = 'Partner_commission_invoice_reminder.Needs_clarification';
    const PARTNER_DEFAULT_REMINDER_SMS_TEMPLATE                 = 'Partner_commission_invoice_reminder.Null';

    const COMMISSION_INVOICE_REMINDER_SMS_TEMPLATE = [
        DetailStatus::ACTIVATED                => self::PARTNER_ACTIVATED_REMINDER_SMS_TEMPLATE,
        DetailStatus::UNDER_REVIEW             => self::PARTNER_UNDER_REVIEW_REMINDER_SMS_TEMPLATE,
        DetailStatus::NEEDS_CLARIFICATION      => self::PARTNER_NEEDS_CLARIFICATION_REMINDER_SMS_TEMPLATE,
        Merchant\Constants::DEFAULT            => self::PARTNER_DEFAULT_REMINDER_SMS_TEMPLATE,
    ];
    /**
     * Used for bulk capture
     */
    const PARTNER_IDS = 'partner_ids';

    const INVOICE_ID  = 'invoice_id';

    /**
     * List of entities for which the commission can be rolled out.
     * The entities defined here must implement the CommissionSourceInterface.
     *
     * @var array
     */
    public static $sourceEntities = [
        self::PAYMENT,
    ];

    /**
     * Types of queries we can make to harvester for commission analytics
     *
     * @var array
     */
    public static $analyticsQueryTypes = [
        self::AGGREGATE_DAILY,
        self::AGGREGATE_DETAIL,
        self::SUBVENTION_DAILY,
        self::SUBVENTION_DETAIL,
    ];

    /**
     * Partner types which are eligible to get commissions even if the source transaction (payment, refund, etc) is
     * not originated by the partner via the partner auth or bearer auth (oauth).
     *
     * Commission will be calculated and rolled out only to the partner types defined here if the transactions do not
     * have the origin set to 'application'.
     *
     * @var array
     */
    public static $partnerTypesEligibleWithoutOrigin = [
        Merchant\Constants::RESELLER,
        Merchant\Constants::AGGREGATOR,
    ];

    /**
     * @return array
     */
    public static function getSourceEntities(): array
    {
        return self::$sourceEntities;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    public static function isValidCommissionSource($entity): bool
    {
        $entityType = $entity->getEntity();

        return (in_array($entityType, self::getSourceEntities(), true) === true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValidQueryType(string $type): bool
    {
        return (in_array($type, self::$analyticsQueryTypes, true) === true);
    }
}
