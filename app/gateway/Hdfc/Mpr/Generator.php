<?php

namespace Gateway\Hdfc\Mpr;

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
        $transactions = array_slice($input, 'transaction');
        $trackids = array_slice($transactions, 'id');

        $hdfcTxns = (new Hdfc\Repository)->retrieveMultipleTransactions($trackids);

        $n = count($input);

        if (count(hdfcTxns) !== $n)
        {
            throw new Exception\LogicException('Hdfc mpr: counts do not match: ' . $n . ' vs ' . count(hdfcTxns));
        }

        $mprArray = array();
        array_push($mprArray, $this->headings);

        for ($i = 0; $i < $n; $i++)
        {
            $values = $this->generateMprRow($input[$i], $hdfcTxns[$i]);
            array_push($mprArray, $values);
        }

        $filename = 'hdfc_mpr.xlsx';

        $fp = fopen($filename, 'w');

        foreach ($mprArray as $row)
        {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $filename;

    }

    protected function generateMprRow($input, $hdfcTxn)
    {
        $amount = $input['transaction']['amount'];

        $msf = $amount * 2 / 100;

        $serviceTax = $amount * self::SERVICE_TAX_PERCENT / 100;
        $educationCess = $amount * self::EDUCATION_CESS_PERCENT / 100;

        $netAmount = $amount - $msf;

        $maskedCardNumber = $this->input['transaction']['card']['iin'] . 'xxxxxx' .
                            $this->input['transaction']['card']['last4'];

        $attributes = array(
            'merchant_code'     => $this->terminal['gateway_merchant_id'],
            'terminal_number'   => $this->terminal['gateway_terminal_id'],
            'rfc_fmt'           => 'BAT',
            'bat_nbr'           => 1,
            'card_type'         => $this->input['transaction']['card']['network'] . ' ' . 'LOCAL',
            'card_number'       => $maskedCardNumber,
            'trans_date'        => (new Carbon('now'))->format('d-M-y'),
            'settle_date'       => (new Carbon('now'))->format('d-M-y'),
            'approv_code'       => '000000',
            'intl_amt'          => 0,
            'domestic_amt'      => $amount,
            'tran_id'           => $hdfcTxn['tranid'],
            'upvalue'           => '`',
            'merchant_trackid'  => $this->input['transaction']['id'],
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

        $mprGenerator = new MprGenerator($attributes);

        (new MockHdfc\Repository)->saveOrFail($mprGenerator);

        return array_values($attributes);
    }
}