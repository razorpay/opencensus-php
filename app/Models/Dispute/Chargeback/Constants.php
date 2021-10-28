<?php

namespace RZP\Models\Dispute\Chargeback;

class Constants
{
    const DISPUTY_TYPE_EXPANSIONS = [
        self::DISPUTE_TYPE_CBK     => 'chargeback',
        self::DISPUTE_TYPE_RR      => 'retrieval',
        self::DISPUTE_TYPE_PRE_ARB => 'pre_arbitration',
        self::DISPUTE_TYPE_ARB     => 'arbitration',
    ];

    const DISPUTE_TYPE_GOODFAITH    = 'GOODFAITH';
    const DISPUTE_TYPE_CBK_REVERSAL = 'CBK reversal';
    const DISPUTE_TYPE_CBK          = 'CBK';
    const DISPUTE_TYPE_RR           = 'RR';
    const DISPUTE_TYPE_PRE_ARB      = 'PRE ARB';
    const DISPUTE_TYPE_ARB          = 'ARB';

    const  INPUT_COLUMN_HEADING_NETWORK        = 'network';
    const  INPUT_COLUMN_HEADING_AMT            = 'amt';
    const  INPUT_COLUMN_DISPUTE_TYPE           = 'dispute_type';
    const  INPUT_COLUMN_HEADING_FULFILMENT_TAT = 'fulfilment_tat';
    const  INPUT_COLUMN_HEADING_REASON_CODE    = 'reason_code';
    const  INPUT_COLUMN_HEADING_TXN_DATE       = 'txn_date';
    const  INPUT_COLUMN_HEADING_RRN            = 'rrn';
    const  INPUT_COLUMN_HEADING_ARN            = 'arn';
    const  INPUT_COLUMN_HEADING_CURRENCY       = 'currency';

    const  OUTPUT_COLUMN_HEADING_REASON_CODE               = 'Reason Code';
    const  OUTPUT_COLUMN_HEADING_IDEMPOTENT_ID             = 'idempotent_id';
    const  OUTPUT_COLUMN_HEADING_ARN                       = 'ARN';
    const  OUTPUT_COLUMN_HEADING_TRANSACTION_DATE          = 'Txn Date';
    const  OUTPUT_COLUMN_HEADING_RRN                       = 'RRN';
    const  OUTPUT_COLUMN_HEADING_FULFILMENT_DATE           = 'Fullfilment date';
    const  OUTPUT_COLUMN_HEADING_MERCHANT_DEADLINE         = 'Merchant deadline';
    const  OUTPUT_COLUMN_HEADING_INITIATION_DATE           = 'Initiation Date';
    const  OUTPUT_COLUMN_HEADING_INITIATION_STATUS         = 'Initiation Status';
    const  OUTPUT_COLUMN_HEADING_PAYMENT_ID                = 'Payment Id';
    const  OUTPUT_COLUMN_HEADING_REPRESENTMENT_AMOUNT      = 'Representment Amount';
    const  OUTPUT_COLUMN_HEADING_CURRENCY                  = 'Currency';
    const  OUTPUT_COLUMN_HEADING_MERCHANT_NAME             = 'Merchant Name';
    const  OUTPUT_COLUMN_HEADING_MERCHANT_ID               = 'merchant_id';
    const  OUTPUT_COLUMN_HEADING_UPFRONT_DEBIT             = 'Upfront Debit [finops to update]';
    const  OUTPUT_COLUMN_HEADING_STATUS                    = 'STATUS';
    const  OUTPUT_COLUMN_HEADING_STATUS_DATE               = 'Status date';
    const  OUTPUT_COLUMN_HEADING_AGENT                     = 'Agent';
    const  OUTPUT_COLUMN_HEADING_TICKTE                    = 'Ticket';
    const  OUTPUT_COLUMN_HEADING_CHARGEBACK_TYPE           = 'Chargeback Type';
    const  OUTPUT_COLUMN_HEADING_PREARB_APPROVAL_STATUS    = 'Prearb approval status - Checker';
    const  OUTPUT_COLUMN_HEADING_PREARB_APPROVAL_COMMENTS  = 'Prearb approval Comments - Checker';
    const  OUTPUT_COLUMN_HEADING_COMMENTS                  = 'Comments';
    const  OUTPUT_COLUMN_HEADING_KEY_ACCOUNT               = 'Key Account';
    const  OUTPUT_COLUMN_HEADING_MID                       = "merchant_id";
    const  OUTPUT_COLUMN_HEADING_NETWORK                   = 'Network';
    const  OUTPUT_COLUMN_HEADING_INTERNATIONAL_TRANSACTION = 'International Transaction';
    const  OUTPUT_COLUMN_HEADING_TRANSACTION_STATUS        = 'Transaction Status';
    const  OUTPUT_COLUMN_HEADING_BASE_AMOUNT               = 'Base Amount';
    const  OUTPUT_COLUMN_HEADING_WEBSITE                   = 'Website';
    const  OUTPUT_COLUMN_HEADING_DISPUTE_ID                = 'Dispute ID';
    const  OUTPUT_COLUMN_HEADING_ME_DEADLINE               = 'ME Deadline';
    const  OUTPUT_COLUMN_HEADING_INITIATION_DATE_2         = 'Initiation date';
    const  OUTPUT_COLUMN_HEADING_REASON_CATEGORY           = 'Reason Category';
    const  OUTPUT_COLUMN_HEADING_NO_DEBIT_LIST             = 'No Debit list';

    const TEAM_NAME = 'team_name';

    const DISPUTE_STATUS_OPEN          = 'Open';
}