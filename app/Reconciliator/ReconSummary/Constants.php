<?php

namespace RZP\Reconciliator\ReconSummary;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;

class Constants
{
    const METHOD                                = 'method';
    const GATEWAY                               = 'gateway';
    const TOTAL_COUNT                           = 'total_count';
    const TOTAL_AMOUNT                          = 'total_amount';
    const RECON_COUNT                           = 'recon_count';
    const RECON_AMOUNT                          = 'recon_amount';
    const UNRECON_COUNT                         = 'unrecon_count';
    const UNRECON_AMOUNT                        = 'unrecon_amount';
    const RECON_COUNT_PERCENTAGE                = 'recon_count_percentage';
    const RECON_AMOUNT_PERCENTAGE               = 'recon_amount_percentage';
    const TXN_COUNT_CONTRIBUTION_PERCENTAGE     = 'txn_count_contribution_%';
    const TXN_AMOUNT_CONTRIBUTION_PERCENTAGE    = 'txn_amount_contribution_%';

    // constants for input params to get recon summary
    const TO                        = 'to';
    const FROM                      = 'from';
    const EMAILS                    = 'emails';
    const UNRECON_DATA_FILE         = 'unrecon_data_file';
    const RECON_SUMMARY_FILE        = 'recon_summary_file';
    const ADDITIONAL_GATEWAYS       = 'additional_gateways';
    const MAX_ALLOWED_UNRECON_COUNT = 'max_allowed_unrecon_count';

    // Payment Params sent in excel attachment
    const PAYMENT_PARAMS = [
        Payment\Entity::ID,
        Payment\Entity::METHOD,
        Payment\Entity::AMOUNT,
        Payment\Entity::STATUS,
        Payment\Entity::DISPUTED,
        Payment\Entity::REFERENCE2,
        Payment\Entity::MERCHANT_ID,
        Payment\Entity::TERMINAL_ID,
        Payment\Entity::CAPTURED_AT,
        Payment\Entity::AUTHORIZED_AT,
        Payment\Entity::AMOUNT_REFUNDED
    ];

    // Refund Params sent in excel attachment
    const REFUND_PARAMS = [
        Refund\Entity::ID,
        Refund\Entity::AMOUNT,
        Refund\Entity::STATUS,
    ];

    // Aggregate params calculated per day per gateway
    const AGGREGATE_PARAMS = [
        self::GATEWAY,
        self::METHOD,
        self::TOTAL_COUNT,
        self::TXN_COUNT_CONTRIBUTION_PERCENTAGE,
        self::TOTAL_AMOUNT,
        self::TXN_AMOUNT_CONTRIBUTION_PERCENTAGE,
        self::RECON_COUNT,
        self::UNRECON_COUNT,
        self::RECON_AMOUNT,
        self::UNRECON_AMOUNT,
        self::RECON_COUNT_PERCENTAGE,
        self::RECON_AMOUNT_PERCENTAGE
    ];

    // Number of unreconciled entities to be sent in attachment
    const LIMIT = 1000;

    const ENTITIES = [
        'Payment',
        'Refund'
    ];

    // Default time duration is 5 days. Summary of last 5 days will be sent in email
    const DURATION = 5;

    // Default key on which result set is sorted
    const RESULT_SORT_KEY = self::UNRECON_COUNT;
}
