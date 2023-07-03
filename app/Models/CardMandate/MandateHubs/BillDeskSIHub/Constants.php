<?php


namespace RZP\Models\CardMandate\MandateHubs\BillDeskSIHub;


class Constants
{
    const PAYMENT                 = 'payment';
    const TERMINAL                = 'terminal';
    const MERCHANT                = 'merchant';
    const TOKEN                   = 'token';
    const GATEWAY                 = 'gateway';
    const ID                      = 'id';
    const CARD_NUMBER             = 'number';
    const CARD                    = 'card';
    const CARD_ID                 = 'id';
    const CARD_LAST4              = 'last4';
    const CARD_NETWORK            = 'network';
    const CARD_TYPE               = 'type';
    const CARD_ISSUER             = 'issuer';
    const CARD_INTERNATIONAL      = 'international';
    const CARD_NAME               = 'name';
    const REDIRECT_URL            = 'redirect_url';
    const STATUS                  = 'status';
    const DEBIT_TYPE              = 'debit_type';
    const CURRENCY                = 'currency';
    const AMOUNT                  = 'amount';
    const MAX_AMOUNT              = 'max_amount';
    const START_TIME              = 'start_time';
    const END_TIME                = 'end_time';
    const FREQUENCY               = 'frequency';
    const TOTAL_CYCLES            = 'total_cycles';
    const INTERVAL                = 'interval';
    const PAUSED_BY               = 'paused_by';
    const CANCELLED_BY            = 'cancelled_by';

    const RECURRING_DEBIT_TYPE            = 'recurring_debit_type';
    const RECURRING_DEBIT_TYPE_INITIAL    = 'initial';
    const RECURRING_DEBIT_TYPE_SUBSEQUENT = 'subsequent';

    const AUTHENTICATION  = 'authentication';
    const AUTHORIZATION   = 'authorization';
    const NOTIFICATION    = 'notification';
    const DELIVERED_AT    = 'delivered_at';
    const CARD_MANDATE    = 'card_mandate';
    const DEBIT_TIME      = 'debit_time';

    const XID                   = 'xid';
    const CAVV2                 = 'cavv2';
    const BILLDESK_SIHUB        = 'billdesk_sihub';

    const FREQUENCY_AS_PRESENTED = 'as_presented';
}
