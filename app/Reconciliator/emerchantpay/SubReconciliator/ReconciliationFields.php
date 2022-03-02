<?php

namespace RZP\Reconciliator\emerchantpay\SubReconciliator;

class ReconciliationFields
{
    const SALE_APPROVED = 'Sale Approved';
    const REFUND_APPROVED = 'Refund Approved';

    const TRANSACTION_DATE_AND_TIME = 'transaction_date_time';
    const MERCHANT_NAME = 'merchant_name';
    const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    const TRANSACTION_TYPE = 'transaction_type';
    const TRANSACTION_AMOUNT = 'transaction_amount';
    const TRANSACTION_CURRENCY = 'transaction_currency';
    const BILLING_CURRENCY = 'billing_currency';
    const BILLING_AMOUNT = 'billing_amount';
    const PROCESSING_TO_BILLING_CURRENCY_EXCHANGE_RATE = 'processing_to_billing_currency_exchange_rate';
    const PAYMENT_TYPE = 'payment_type';
    const CARD_TYPE = 'card_type';
    const CARD_SUB_TYPE = 'card_sub_type';
    const REGION_CLASS = 'region_class';
    const AUTHCODE = 'authcode';
    const STANDARD_DEBIT_CARD_RATE = 'standard_debit_card_rate';
    const TRANSACTION_FEE_CURRENCY = 'transaction_fee_currency';
    const TRANSACTION_FEE_AMOUNT = 'transaction_fee_amount';
    const COMMISSION_PERCENT = 'commission_percent';
    const INTERCHANGE_FEE = 'interchange_fee';
    const INTERCHANGE_CURRENCY = 'interchange_currency';
    const SCHEME_FEE = 'scheme_fee';
    const SCHEME_FEE_CURRENCY = 'scheme_fee_currency';
    const COMMISSION_AMOUNT = 'commission_amount';
    const UNIQUE_ID = 'unique_id';
    const ARN = 'arn';
    const GATEWAY = 'gateway';
    const BUSINESS_DATA = 'business_data';
}
