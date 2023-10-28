<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Models\Merchant\Constants as MerchantConstants;

class Constants
{
    const INVOICE_ID    = 'invoice_id';
    const INVOICE_IDS   = 'invoice_ids';
    const CREATE_TDS    = 'create_tds';
    const PARTNER_IDS   = 'partner_ids';
    const INVOICE_MONTH = 'invoice_month';
    const REMINDER      = 'reminder';
    const PAYLOAD       = 'payload';
    const CREATED_AT    = 'created_at';
    const ACTION        = 'action';

    const CREATE_AND_SETTLEMENT       = 'create_and_settlement';
    const SETTLEMENT                  = 'settlement';
	const CREATE_ISSUED               = 'create_issued';
	const CREATE_AND_FINANCE_WORKFLOW = 'create_and_finance_workflow';
	const FINANCE_WORKFLOW            = 'finance_workflow';

    const SKIP_PROCESSED        = 'skip_processed';
    const UPDATE_INVOICE_STATUS = 'update_invoice_status';

    // Minimum no.of subM MTUs needed to be present for a partner to generate invoice
    const GENERATE_INVOICE_MIN_SUB_MTU_COUNT = 3;

    const INVOICE_TNC_UPDATED_TIMESTAMP = 1672531200;

    const GSTIN_AUTO_APPROVAL_ENABLED_PARTNER_TYPES = [MerchantConstants::RESELLER];

    const DEFAULT_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE              = 'emails.mjml.merchant.partner.commission_invoice.issued';
    const DEFAULT_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE_MY_REGION    = 'emails.mjml.merchant.partner.commission_invoice.my_issued';
    const RESELLER_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE             = 'emails.mjml.merchant.partner.commission_invoice.reseller_merchant_issued';
    const DEFAULT_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX     = 'emails.mjml.merchant.partner.commission_invoice.reminder';
    const RESELLER_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX    = 'emails.mjml.merchant.partner.commission_invoice.reminder.reseller';
}
