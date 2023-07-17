<?php
namespace RZP\Reconciliator\Icici\SubReconciliator;

class ReconciliationFields
{
    const TRANSACTION_DATE = ['Transaction Date'];

    const TRANSACTION_TIME = ['Transaction Time'];

    const TRANSACTION_ID = ['Transaction ID'];

    const ACTION_CODE = ['Transaction Action'];

    const MERCHANT_AGGREGATOR_ID = ['merchant_aggregator_id', 'merchantaggregatorid'];

    const MERCHANT_ID = ['Merchant ID'];

    const TERMINAL_ID = ['Terminal ID'];

    const CARD_NUMBER = ['Card Number'];

    // Card Type
    const CARD_TYPE = ['Instrument Type'];

    const MRCH_CTG_CODE = ['MRCH CTG CODE'];

    const MCC_CATEGORY = 'mcc_category';

    const TRANSACTION_TYPE = ['transaction_type', 'transactiontype'];

    const CURRENCY_CODE = ['Currency Code'];

    const TRANSACTION_AMOUNT = ['Transaction Amount'];


    const AUTH_CODE = ['authapproval_code', 'authapprovalcode'];

    const RRN = ['Retrieval Reference Number'];

    const ARN = 'arn';

    const PAYMENT_ID = 'Payment ID';


    const MERCHANT_TRACK_ID = ['Merchant Track ID'];

    const TRANSACTION_STATUS = ['Transaction Status'];
}
