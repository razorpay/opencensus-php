<?php

namespace Gateway\Hdfc\Mpr;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Gateway\Hdfc;
use Models\Base;
use Models\Payment;

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
        $hdfcPayments = $this->fetchHdfcPayments($input);

        $mprArray = $this->generateMprArray($input, $hdfcPayments);

        $filename = $this->generateMprFile($mprArray);

        return $filename;
    }

    protected function fetchHdfcPayments($input)
    {
        $payments = array_column($input, 'payment');
        $trackids = array_column($payments, 'id');

        $hdfcPayments = (new Hdfc\Repository)->retrieveCapturedPayments($trackids);

        $n = count($input);

        if (count($hdfcPayments) !== $n)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: counts do not match: ' . $n . ' vs ' . count($hdfcPayments));
        }

        return $hdfcPayments;
    }

    protected function generateMprFile($data)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y_H:i:s');
        $filename =  'Hdfc_Mpr_'.$time;

        $excel = Excel::create($filename, function($excel) use ($data)
        {
            $excel->sheet('Hdfc Mpr File', function($sheet) use ($data)
                {
                    $sheet->with($data, false, false);
                });
        });

        $fileMetadata = $excel->store('xlsx', storage_path('files/settlement'), true);
        $fullFileName = $fileMetadata['full'];

        return $fullFileName;
    }

    protected function generateMprArray($input, $hdfcPayments)
    {
        $mprArray = array();

        array_push($mprArray, $this->headings);

        $count = count($input);

        for ($i = 0; $i < $count; $i++)
        {
            $values = $this->generateMprRow($input[$i], $hdfcPayments[$i]);
            array_push($mprArray, $values);
        }

        return $mprArray;
    }

    protected function generateMprRow($input, $hdfcPayment)
    {
        $amount = $input['payment']['amount'] / 100;

        $msf = $amount * 2 / 100;

        $serviceTax = $msf * self::SERVICE_TAX_PERCENT / 100;
        $educationCess = $msf * self::EDUCATION_CESS_PERCENT / 100;

        $this->roundUp($msf);
        $this->roundUp($serviceTax);
        $this->roundUp($educationCess);

        $netAmount = $amount - ($msf + $serviceTax + $educationCess);

        $maskedCardNumber = $input['card']['iin'] . 'xxxxxx' .
                            $input['card']['last4'];

        $capturedAt = $input['payment']['captured_at'];
        $capturedAt = (new Carbon('Asia/Kolkata'))->setTimestamp($capturedAt);

        $captureDate = $capturedAt->format('d-M-y');
        $setlDate = $capturedAt->addDay(1)->format('d-M-y');

        $trackid = Payment\Entity::getIdPrefix() . $input['payment']['id'];

        $attributes = array(
            'merchant_code'     => $input['terminal']['gateway_merchant_id'],
            'terminal_number'   => $input['terminal']['gateway_terminal_id'],
            'rfc_fmt'           => 'BAT',
            'bat_nbr'           => 1,
            'card_type'         => $input['card']['network'] . ' ' . 'LOCAL',
            'card_number'       => $maskedCardNumber,
            'trans_date'        => $captureDate,
            'settle_date'       => $setlDate,
            'approv_code'       => '000000',
            'intl_amt'          => 0,
            'domestic_amt'      => $amount,
            'tran_id'           => (int) $hdfcPayment['gateway_payment_id'],
            'upvalue'           => '`',
            'merchant_trackid'  => $trackid,
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
            'sequence_number'   => $hdfcPayment['ref'],
        );

        return array_values($attributes);
    }

    protected function roundUp(& $amount)
    {
        $amount = ceil($amount * 100) / 100;
    }
}