<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Transaction;

class Settlement
{
    protected $headings = array(
        'Client_Code',
        'Product_Code',
        'Payment_Type',
        'Payment_Ref_No.',
        'Payment_Date',
        'Instrument Date',
        'Dr_Ac_No',
        'Amount',
        'Bank_Code_Indicator',
        'Beneficiary_Code',
        'Beneficiary_Name',
        'Beneficiary_Bank',
        'IFSC Code',
        'Beneficiary_Acc_No',
        'Location',
        'Print_Location',
        'Instrument_Number',
        'Beneficiary_Address_1',
        'Beneficiary_Address_2',
        'Beneficiary_Address_3',
        'Beneficiary_Address_4',
        'Beneficiary_Email',
        'Beneficiary_Mobile',
        'Debit_Narration',
        'Credit_Narration',
        'Payment Details 1',
        'Payment Details 2',
        'Payment Details 3',
        'Payment Details 4',
        'Enrichment_1',
        'Enrichment_2',
        'Enrichment_3',
        'Enrichment_4',
        'Enrichment_5',
        'Enrichment_6',
        'Enrichment_7',
        'Enrichment_8',
        'Enrichment_9',
        'Enrichment_10',
        'Enrichment_11',
        'Enrichment_12',
        'Enrichment_13',
        'Enrichment_14',
        'Enrichment_15',
        'Enrichment_16',
        'Enrichment_17',
        'Enrichment_18',
        'Enrichment_19',
        'Enrichment_20',
        'Symbol',
        'Text File');

    public function generateSettlementFile($settlements, $txns)
    {
        ;
    }
}
