<?php

namespace RZP\Models\Growth;

class Constants
{
    const TYPE        = 'type';
    const DATA        = 'data';
    const CODE        = 'code';
    const MESSAGE     = 'message';
    const RESPONSE    = 'response';
    const MERCHANT_ID = 'merchant_id';

    const TRANSACTOR_ID = 'transactor_id';
    const JOURNAL_ID    = 'journal_id';
    const ERROR         = 'ERROR';
    const PACKAGE_NAME = 'package_name';
    const AMOUNT = "amount";
    const CURRENCY = "currency";
    const IS_REVERSAL = 'is_reversal';
    const CAMPAIGN_NAME = "campaign_name";
    const EXPIRED_AT = "expired_at";
    const PRICING_PLAN_ID = "pricing_plan_id";
    const TEMPLATE_NAME = "template_name";
    const EMAIL_SUBJECT = "subject";

    // pricing bundle email types
    const PAYMENT_SUCCESS = 'payment_success';
    const PAYMENT_FAILURE = 'payment_failure';
    const WELCOME = 'welcome';
    const PLAN_UPDATED = 'plan_updated';
    const DEFAULT_TYPE = 'default';
}
