<?php

namespace Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use Models\Refund;
use Models\Payment;
use Models\Gateway;
use Models\Bank\IFSC;
// use Gateway\Netbanking\Base\Gateway;

class DailyFiles
{
    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();

        $this->app = \App::getFacadeRoot();

        $this->mode = $this->app['basicauth']->getMode();
    }

    public function generate($input)
    {
        list($from, $to) = $this->getTimestamps($input);

        // $refundFile = $this->getRefundsData($from, $to);

        $claimsFile = $this->getClaimsData($from, $to);

        $amount = [];
        $amount['claims'] = '';
        $amount['refunds'] = '';
        $amount['total'] = '';

        $this->sendMail($amount, $claimsFile, $refundsFile);
    }

    protected function getRefundsData($from, $to)
    {
        $bankCode = IFSC::KKBK;

        $gateway = Payment\Gateway::$netbankingToGatewayMap[$bankCode];

        $refunds = (new Refund\Repository)->fetchRefundsForBankBetweenTimestamps(
                                                $bankCode, $from, $to, $gateway);

        $count = $refunds->count();

        if ($count === 0)
        {
            return ['count' => $count];
        }

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $refund->payment->toArray();
            $col['terminal'] = $refund->payment->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $terminal->getGateway();

        $action = 'generateRefunds';

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
    }

    protected function getClaimsData($from, $to)
    {
        $bankCode = IFSC::KKBK;

        $gateway = Payment\Gateway::$netbankingToGatewayMap[$bankCode];

        $status = [Payment\Status::CAPTURED, Payment\Status::REFUNDED];

        $claims = (new Payment\Repository)->
                        fetchPaymentsWithStatus($from, $to, $gateway, $status);

        $data = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $claim->terminal->getGateway();

        $action = 'generateClaims';

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        sd($claims);


    }


    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;

        if (isset($input['on']))
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');
            $from->hour(0)->minute(0)->second(0);

            $fromTimeStamp = $from->timestamp;

            $to = $from->addDay()->timestamp - 1;

            $from = $fromTimeStamp;
        }
        else
        {
            if (isset($input['from']))
            {
                $from = $input['from'];
            }

            if (isset($input['to']))
            {
                $to = $input['to'];
            }
        }

        return array($from, $to);
    }

    protected function sendMail($amount, $claimsFilePath, $refundFilePath)
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $data = [
            'subject'     => 'Kotak Netbanking claims and refund files for '.$today,
            'amount'      => $amount,
            'claimsFile'  => $claimsFilePath,
            'refundsFile' => $refundFilePath,
        ];

        $this->mail->queue('emails.admin.kotak_refunds', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Netbanking Refunds');

            $message->subject($data['subject']);

            $message->to($emails);

            $message->attach($data['claimsFile']);

            $message->attach($data['refundsFile']);
        });
    }

}
