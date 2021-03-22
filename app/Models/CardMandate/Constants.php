<?php


namespace RZP\Models\CardMandate;


class Constants
{
    // Mandate HQ input and output parameter names
    const MANDATE_HQ_MANDATE_REGISTER_ID     = 'mandate_register_id';
    const MANDATE_HQ_REDIRECT_URL            = 'redirect_url';
    const MANDATE_HQ_TRUE                    = 'true';
    const MANDATE_HQ_FALSE                   = 'false';
    const MANDATE_HQ_APPROVED                = 'approved';
    const MANDATE_HQ_MANDATE_ID              = 'mandateId';
    const MANDATE_HQ_INSTRUMENT              = 'instrument';
    const MANDATE_HQ_INSTRUMENT_ID           = 'id';
    const MANDATE_HQ_INSTRUMENT_EXPIRY       = 'expiry';
    const MANDATE_HQ_INSTRUMENT_METHOD       = 'method';
    const MANDATE_HQ_INSTRUMENT_TYPE         = 'type';
    const MANDATE_HQ_MERCHANT                = 'merchant';
    const MANDATE_HQ_MAX_AMOUNT              = 'max_amount';
    const MANDATE_HQ_AMOUNT                  = 'amount';
    const MANDATE_HQ_CURRENCY                = 'currency';
    const MANDATE_HQ_FREQUENCY               = 'frequency';
    const MANDATE_HQ_CALLBACK                = 'callback';
    const MANDATE_HQ_END_TIME                = 'end_time';
    const MANDATE_HQ_DEBIT_TYPE              = 'debit_type';

    // Mandate HQ constant input values
    const MANDATE_HQ_INSTRUMENT_METHOD_CARD = 'Card';
    const MANDATE_HQ_INSTRUMENT_TYPE_CARD   = 'Card';
    const MANDATE_HQ_FREQUENCY_AD_HOC       = 'adhoc';
    const MANDATE_HQ_MAX_AMOUNT_DEFAULT     = 200000;
    const MANDATE_HQ_DEBIT_TYPE_MAX_AMOUNT  = 'max_amount';

    const MANDATE_HQ_REDIRECT_ROUTE_NAME        = 'payment_mandate_hq_redirect_authenticate';
}
