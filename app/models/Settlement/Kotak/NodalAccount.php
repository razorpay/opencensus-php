<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Excel;
use Models\Base;
use Models\Merchant;
use Models\Transaction;

class NodalAccount
{
    use FileHandlerTrait;

    protected static $filename = 'Kotak_Settlement';

    public static $headings = array(
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
        'Enrichment_20');

    public function __construct()
    {
        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today('Asia/Kolkata')->format('d/m/y');
    }

    public function generateSettlementFile($settlements, $txns)
    {
        $data = array();

        $txt = '';

        foreach ($settlements as $settlement)
        {
            $merchant = $settlement->merchant;

            $ba = $merchant->bankAccount;

            $array = array(
                'Client_Code'           => 'NODAL',
                'Product_Code'          => 'CMSPAY',
                'Payment_Type'          => 'NEFT',
                'Payment_Ref_No.'       => $settlement->getPublicId(),
                'Payment_Date'          => $this->date,
                'Dr_Ac_No'              => '1209034',
                'Amount'                => $settlement->getAmount() / 100,
                'Bank_Code_Indicator'   => 'M',
                'Beneficiary_Name'      => $ba->getBeneficiaryName(),
                'IFSC Code'             => $ba->getIfscCode(),
                'Beneficiary_Acc_No'    => $ba->getAccountNumber(),
                'Payment Details 1'     => $merchant->getPublicId()
                );

            $values = $this->getAllValues($array);

            array_push($data, $values);
        }

        $txt = $this->generateText($data);

        return $this->writeToTextFile($txt);
    }

    protected function generateFile($txt)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y_H:i:s');
        $name = 'Kotak_Settlement_'.$time.'.txt';
        $path = storage_path() . '/files/settlement/';

        $fullpath = $path . $name;

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);

        return $fullpath;
    }

    protected function getEmptyArray()
    {
        $count = count(static::$headings);

        return array_combine(static::$headings, array_fill(0, $count, null));
    }

    protected function getAllValues($partialValues)
    {
        $dict = $this->getEmptyArray();

        foreach ($partialValues as $key => $value)
        {
            $dict[$key] = $value;
        }

        return array_values($dict);
    }

    public static function getHeadings()
    {
        return static::$headings;
    }
}
