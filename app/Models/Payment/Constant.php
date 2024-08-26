<?php

namespace RZP\Models\Payment;

class Constant
{
    const ELASTIC_SEARCH_ON_CREATED_AT_THEN_ON_SCORE_KEY = 'app.es_search_on_created_at_then_on_score_experiment_id';

    const VARIANT_ENABLE = 'enable';

    const ONLINE = 'online';
    const SOURCE_CHANNEL = 'source_channel';

    const JPMC_IMPORT_FLOW_GOODS_DESCRIPTION_DEFAULT_OTHER = 'Other';

    const PAYMENT_CAPTURE_EVENTS = 'payment-capture-events-';

    const PAYMENT_CAPTURED = 'payment_captured';

    const ERROR_MONEY_IMPLICATION = 'error_money_implication';

    const ERROR_NEXT_STEP         = 'error_next_step';

    const MONEY_IMPLICATION       = 'money_implication';

    const NEXT_STEP               = 'next_step';

    const PERCEPTION              = 'perception';

    const ERROR_PERCEPTION        = 'error_perception';

    const MERCHANT_DESC           = 'merchant_desc';

    const ERROR_MERCHANT_DESC     = 'error_merchant_desc';

    const RZP_ACTIONABLE          = 'rzp_actionable';

    const ERROR_RZP_ACTIONABLE    = 'error_rzp_actionable';


    const PAYMENT_ID              = 'payment_id';

    const S0101 = 'S0101';
    const S0102 = 'S0102';

    //list of codes for opgsp import flow for awb upload
    const OPGSP_AWB_REQUIRED = [
        self::S0101,
        self::S0102,
    ];

    const SETTLEMENT_ONHOLD = 'settlement_onhold';
}
