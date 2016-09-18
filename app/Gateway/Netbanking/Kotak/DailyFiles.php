<?php

namespace RZP\Gateway\Netbanking\Kotak;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;

class DailyFiles
{
    protected $app;
    protected $mail;
    protected $repo;
    protected $mode;
    protected $gateway;
    protected $bankCode;

    // SECONDS_PER_DAY is 24 Hours/Day * 60 Minutes/Hour * 60 Seconds/Minute
    //                 is 86400
    const SECONDS_PER_DAY = 86400;

    public function __construct()
    {
        $this->mail = Mail::getFacadeRoot();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

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

        // Send the mail only when amount of refunds is greater than zero
        if ($amount['refunds'] + $amount['claims']> 0)
        {
            $this->sendMail($amount, $claimsFile, $refundsFile);
        }

        return [$refundsFile, $claimsFile];
    }

    protected function getRefundsData($from, $to)
    {
        $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
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

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function getClaimsData($from, $to)
    {
        $status = [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED, Payment\Status::REFUNDED];

        // Payment made yesterday would have been reconciled today, hence the from, to
        // have to be forwarded by a day
        list($from, $to) = $this->updateTimeStamps($from, $to);

        // Fetch payment which have been reconciled in this period
        $claims = $this->repo->payment->
                        fetchReconciledPaymentsForGateway($from, $to, $this->gateway, $status);

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

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function updateTimeStamps($from, $to)
    {
        $tsDifference = self::SECONDS_PER_DAY;

        return [$from + $tsDifference, $to + $tsDifference];
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
