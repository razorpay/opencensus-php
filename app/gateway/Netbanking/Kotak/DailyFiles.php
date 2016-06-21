<?php

namespace Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use Models\Payment;
use Models\Gateway;
use Models\Bank\IFSC;

class DailyFiles
{
    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();

        $this->app = \App::getFacadeRoot();

        $this->mode = $this->app['basicauth']->getMode();

        $this->bankCode = IFSC::KKBK;

        $this->gateway = Payment\Gateway::$netbankingToGatewayMap[$this->bankCode];
    }

    public function generate($from, $to)
    {
        list($refundAmount, $refundsFile) = $this->getRefundsData($from, $to);

        list($claimsAmount, $claimsFile) = $this->getClaimsData($from, $to);

        $amount = [];
        $amount['claims'] = $claimsAmount;
        $amount['refunds'] = $refundAmount;
        $amount['total'] = $claimsAmount - $refundAmount;

        $this->sendMail($amount, $claimsFile, $refundsFile);

        return [$refundsFile, $claimsFile];
    }

    protected function getRefundsData($from, $to)
    {
        $refunds = (new Payment\Refund\Repository)->fetchRefundsForGatewayBetweenTimestamps(
                                    Payment\Entity::BANK, $this->bankCode, $from, $to, $this->gateway);

        $count = $refunds->count();

        if ($count === 0)
        {
            return [0, ''];
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

        return Gateway::call($gateway, $action, $input, $this->mode);
    }

    protected function getClaimsData($from, $to)
    {
        $status = [Payment\Status::CAPTURED, Payment\Status::REFUNDED];

        $claims = (new Payment\Repository)->
                        fetchPaymentsWithStatus($from, $to, $this->gateway, $status);

        if ($claims->count() === 0)
        {
            return [0, ''];
        }

        $data = [];

        foreach ($claims as $claim)
        {
            $col = [];

            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $claim->terminal->getGateway();

        $action = 'generateClaims';

        return Gateway::call($gateway, $action, $input, $this->mode);
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

            if (empty($data['claimsFile']) === false)
            {
                $message->attach($data['claimsFile']);
            }

            if (empty($data['refundsFile']) === false)
            {
                $message->attach($data['refundsFile']);
            }
        });
    }

}
