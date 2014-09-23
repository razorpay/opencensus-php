<?php

namespace Gateway\Hdfc\Mpr;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Models\Base;

class Generator
{
    protected $headings = array(
        'merchant_code',
        'terminal_number',
        'rfc_fmt',
        'bat_nbr',
        'card_type',
        'card_number',
        'trans_date',
        'settle_date',
        'approv_code',
        'intl_amt',
        'domestic_amt',
        'tran_id',
        'upvalue',
        'merchant_trackid',
        'msf',
        'service_tax',
        'edu_cess',
        'net_amount',
        'debitcredit_type',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5',
        'sequence_number',
    );

    const SERVICE_TAX_PERCENT = 12;
    const EDUCATION_CESS_PERCENT = 0.36;

    public function generateMpr(array $input)
    {
        $hdfcTxns = $this->fetchHdfcTransactions($input);

        $mprArray = $this->generateMprArray($input, $hdfcTxns);

        $filename = $this->generateMprFile($mprArray);

        return $filename;
    }

    protected function fetchHdfcTransactions($input)
    {
        $transactions = array_column($input, 'transaction');
        $trackids = array_column($transactions, 'id');

        $hdfcTxns = (new Hdfc\Repository)->retrieveCapturedTransactions($trackids);

        $n = count($input);

        if (count($hdfcTxns) !== $n)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: counts do not match: ' . $n . ' vs ' . count($hdfcTxns));
        }

        return $hdfcTxns;
    }

    protected function generateMprFile($mprArray)
    {
        // @todo: remove sys_get_temp_dir. the doc comments don't recommend it.
        // Create a temp file name
        $filename =  tempnam(sys_get_temp_dir(), 'hdfc_mpr') . '.xlsx';

        $fp = fopen($filename, 'w');

        foreach ($mprArray as $row)
        {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $filename;
    }

    protected function generateMprArray($input, $hdfcTxns)
    {
        $mprArray = array();

        array_push($mprArray, $this->headings);

        $count = count($input);

        for ($i = 0; $i < $count; $i++)
        {
            $values = $this->generateMprRow($input[$i], $hdfcTxns[$i]);
            array_push($mprArray, $values);
        }

        return $mprArray;
    }

    protected function generateMprRow($input, $hdfcTxn)
    {
        $amount = $input['transaction']['amount'] / 100;

        $msf = $amount * 2 / 100;

        $serviceTax = $msf * self::SERVICE_TAX_PERCENT / 100;
        $educationCess = $msf * self::EDUCATION_CESS_PERCENT / 100;

        $this->roundUp($msf);
        $this->roundUp($serviceTax);
        $this->roundUp($educationCess);

        $netAmount = $amount - ($msf + $serviceTax + $educationCess);

        $maskedCardNumber = $input['card']['iin'] . 'xxxxxx' .
                            $input['card']['last4'];

        $capturedAt = $input['transaction']['captured_at'];
        $capturedAt = (new Carbon('Asia/Kolkata'))->setTimestamp($capturedAt);

        $transactionDate = $capturedAt->format('d-M-y');
        $setlDate = $capturedAt->addDay(1)->format('d-M-y');

        $attributes = array(
            'merchant_code'     => $input['terminal']['gateway_merchant_id'],
            'terminal_number'   => $input['terminal']['gateway_terminal_id'],
            'rfc_fmt'           => 'BAT',
            'bat_nbr'           => 1,
            'card_type'         => $input['card']['network'] . ' ' . 'LOCAL',
            'card_number'       => $maskedCardNumber,
            'trans_date'        => $transactionDate,
            'settle_date'       => $setlDate,
            'approv_code'       => '000000',
            'intl_amt'          => 0,
            'domestic_amt'      => $amount,
            'tran_id'           => $hdfcTxn['gateway_transaction_id'],
            'upvalue'           => '`',
            'merchant_trackid'  => 'txn-'.$input['transaction']['id'],
            'msf'               => $msf,
            'service_tax'       => $serviceTax,
            'edu_cess'          => $educationCess,
            'net_amount'        => $netAmount,
            'debitcredit_type'  => 'CC',
            'udf1'              => '',
            'udf2'              => '',
            'udf3'              => '',
            'udf4'              => '',
            'udf5'              => '',
            'sequence_number'   => $hdfcTxn['ref'],
        );

        return array_values($attributes);
    }

    protected function roundUp(& $amount)
    {
        $amount = ceil($amount * 100) / 100;
    }
}