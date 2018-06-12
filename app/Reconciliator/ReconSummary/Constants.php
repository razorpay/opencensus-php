<?php

namespace RZP\Reconciliator\ReconSummary;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;

class Constants
{
    const TOTAL_COUNT               = 'total_count';
    const TOTAL_AMOUNT              = 'total_amount';
    const RECON_COUNT               = 'recon_count';
    const RECON_AMOUNT              = 'recon_amount';
    const UNRECON_COUNT             = 'unrecon_count';
    const UNRECON_AMOUNT            = 'unrecon_amount';
    const RECON_COUNT_PERCENTAGE    = 'recon_count_percentage';
    const RECON_AMOUNT_PERCENTAGE   = 'recon_amount_percentage';

    // List of gateways included in recon summary mail generation
    const GATEWAYS = [
        'ebs',
        'amex',
        'hdfc',
        'atom',
        'axis_migs',
        'cybersource',
        'kotak',
        'hitachi',
        'billdesk',
        'mobikwik',
        'first_data',
        'upi_icici',
        'upi_mindgate',
        'upi_hulk',
        'card_fss',
        'enach_rbl',
        'netbanking_icici',
        'netbanking_rbl',
        'netbanking_bob',
        'netbanking_hdfc',
        'netbanking_kotak',
        'netbanking_axis',
        'netbanking_federal',
        'wallet_olamoney',
        'wallet_payzapp',
        'wallet_freecharge',
        'wallet_mpesa',
        'wallet_sbibuddy',
        'wallet_airtelmoney',
        'wallet_jiomoney',
    ];

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
        self::TOTAL_COUNT,
        self::TOTAL_AMOUNT,
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
    const RESULT_SORT_KEY = self::TOTAL_AMOUNT;
}
