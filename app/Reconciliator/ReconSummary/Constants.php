<?php

namespace RZP\Reconciliator\ReconSummary;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;

class Constants
{
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
        'total_count',
        'total_amount',
        'recon_count',
        'unrecon_count',
        'recon_amount',
        'unrecon_amount',
        'recon_count_percentage',
        'recon_amount_percentage'
    ];

    // Number of unreconciled entities to be sent in attachment
    const LIMIT = 1000;

    const ENTITIES = [
        'Payment',
        'Refund'
    ];

    // Default time duration is 5 days. Summary of last 5 days will be sent in email
    const DURATION = 5;
}