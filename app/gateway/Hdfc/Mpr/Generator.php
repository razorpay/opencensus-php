<?php

namespace Gateway\Hdfc\Mpr;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Gateway\Hdfc;
use Models\Base;
use Models\Payment;
use Trace\TraceCode;

class Generator
{
    protected $headings = array(
        'merchant_code',
        'terminal_number',
        'rec_fmt',
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

    public function __construct()
    {
        $this->trace = \App::getFacadeRoot()['trace'];
    }

    public function generateMpr(array $input)
    {
        $this->trace->info(TraceCode::MPR_HDFC_GEN_INITIATED);

        $hdfcPayments = $this->fetchHdfcPayments($input['payments']);

        $mprArrayPayments = $this->generateMprArray($input['payments'], $hdfcPayments);

        $hdfcRefunds = $this->fetchHdfcRefunds($input['refunds']);

        $mprArrayRefunds = $this->generateMprArray($input['refunds'], $hdfcRefunds);

        $mprArray = array_merge(
            [$this->headings],
            $mprArrayPayments,
            $mprArrayRefunds);

        $filename = $this->generateMprFile($mprArray);

        return $filename;
    }

    protected function fetchHdfcPayments($input)
    {
        $payments = array_column($input, 'payment');
        $paymentIds = array_column($payments, 'id');

        $hdfcPayments = (new Hdfc\Repository)->retrieveCapturedPayments($paymentIds);

        $n = count($input);

        if (count($hdfcPayments) !== $n)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: counts do not match: ' . $n . ' vs ' . count($hdfcPayments));
        }

        $this->trace->debug(TraceCode::MPR_HDFC_PAYMENTS_FETCHED);

        return $hdfcPayments;
    }

    protected function fetchHdfcRefunds($input)
    {
        if (count($input) === 0)
            return [];

        $refunds = array_column($input, 'refund');
        $refundIds = array_column($refunds, 'id');

        $hdfcRefunds = (new Hdfc\Repository)->retrieveRefunds($refundIds);

        $n = count($input);

        if (count($hdfcRefunds) !== $n)
        {
            throw new Exception\LogicException(
                'Hdfc mpr: counts do not match: ' . $n . ' vs ' . count($hdfcRefunds));
        }

        $this->trace->debug(TraceCode::MPR_HDFC_PAYMENTS_FETCHED);

        return $hdfcRefunds;
    }

    protected function generateMprFile($data)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y_H:i:s');
        $filename =  'Hdfc_Mpr_'.$time;

        $excel = Excel::create($filename, function($excel) use ($data)
        {
            $excel->sheet('Hdfc Mpr File', function($sheet) use ($data)
                {
                    $sheet->fromArray($data, null, 'A1', true, false);
                });
        });

        $fileMetadata = $excel->store('xlsx', storage_path('files/settlement'), true);
        $fullFileName = $fileMetadata['full'];

        $this->trace->info(TraceCode::MPR_HDFC_FILE_GENERATED);

        return $fullFileName;
    }

    protected function generateMprArray($input, $hdfcTransactions)
    {
        $mprArray = array();

        $count = count($input);

        for ($i = 0; $i < $count; $i++)
        {
            $values = $this->generateMprRow($input[$i], $hdfcTransactions[$i]);
            array_push($mprArray, $values);
        }

        return $mprArray;
    }

    protected function generateMprRow($input, $hdfcTransaction)
    {
        $type = 'payment';

        if (isset($input['refund']))
        {
            $type = 'refund';
        }

        $amount = $msf = $serviceTax = $educationCess = $netAmount = $transactedAt = 0;
        $trackid = null;
        $recFmt = '';

        if ($type === 'payment')
        {
            $data = $this->getDataForPayment($input);
            $amount = $input['payment']['amount'] / 100;

            $msf = $amount * 2 / 100;

            $serviceTax = $msf * self::SERVICE_TAX_PERCENT / 100;
            $educationCess = $msf * self::EDUCATION_CESS_PERCENT / 100;

            $this->roundUp($msf);
            $this->roundUp($serviceTax);
            $this->roundUp($educationCess);

            $netAmount = $amount - ($msf + $serviceTax + $educationCess);

            $transactedAt = $input['payment']['captured_at'];

            $trackid = $input['payment']['id'];
            $recFmt = 'BAT';
        }
        else if ($type === 'refund')
        {
            $data = $this->getDataForRefund($input);

            $amount = $input['refund']['amount'] / 100;

            $msf = $serviceTax = $educationCess = 0;

            $netAmount = $amount;

            $transactedAt = $input['refund']['created_at'];
            $trackid = $input['refund']['id'];
            $recFmt = 'CVD';
        }

        $maskedCardNumber = $input['card']['iin'] . 'xxxxxx' .
                            $input['card']['last4'];

        $transactedAt = (new Carbon('Asia/Kolkata'))->setTimestamp($transactedAt);

        $transactDate = $transactedAt->format('d-M-y');
        $setlDate = $transactedAt->addDay(1)->format('d-M-y');

        $attributes = array(
            'merchant_code'     => $input['terminal']['gateway_merchant_id'],
            'terminal_number'   => $input['terminal']['gateway_terminal_id'],
            'rec_fmt'           => $recFmt,
            'bat_nbr'           => 1,
            'card_type'         => $input['card']['network'] . ' ' . 'LOCAL',
            'card_number'       => $maskedCardNumber,
            'trans_date'        => $transactDate,
            'settle_date'       => $setlDate,
            'approv_code'       => '000000',
            'intl_amt'          => 0,
            'domestic_amt'      => $amount,
            'tran_id'           => (int) $hdfcTransaction['gateway_transaction_id'],
            'upvalue'           => '`',
            'merchant_trackid'  => $trackid,
            'msf'               => $msf,
            'service_tax'       => $serviceTax,
            'edu_cess'          => $educationCess,
            'net_amount'        => $netAmount,
            'debitcredit_type'  => 'DC',
            'udf1'              => '',
            'udf2'              => '',
            'udf3'              => '',
            'udf4'              => '',
            'udf5'              => '',
            'sequence_number'   => $hdfcTransaction['ref'],
        );

        return array_values($attributes);
    }

    protected function getDataForPayment($input)
    {
        $amount = $input['payment']['amount'] / 100;

        $msf = $amount * 2 / 100;

        $serviceTax = $msf * self::SERVICE_TAX_PERCENT / 100;
        $educationCess = $msf * self::EDUCATION_CESS_PERCENT / 100;

        $this->roundUp($msf);
        $this->roundUp($serviceTax);
        $this->roundUp($educationCess);

        $netAmount = $amount - ($msf + $serviceTax + $educationCess);

        $transactedAt = $input['payment']['captured_at'];

        $trackid = $input['payment']['id'];
        $recFmt = 'BAT';

    }

    protected function getDataForRefund($input)
    {
        $amount = $input['refund']['amount'] / 100;

        $msf = 0;

        $serviceTax = 0;
        $educationCess = 0;

        $netAmount = $amount;

        $transactedAt = $input['refund']['created_at'];
        $trackid = $input['refund']['id'];
        $recFmt = 'CVD';
    }

    protected function roundUp(& $amount)
    {
        $amount = ceil($amount * 100) / 100;
    }
}