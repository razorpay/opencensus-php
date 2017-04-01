<?php

namespace RZP\Gateway\Netbanking\Base;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
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

    public function __construct($bankCode)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->mode = $this->app['basicauth']->getMode();

        $this->gateway = Payment\Gateway::$netbankingToGatewayMap[$bankCode];

        $this->bankCode = $bankCode;
    }

    public function generate($from, $to)
    {
        list($refundAmount, $refundsFile) = $this->getRefundsData($from, $to);

        list($claimAmount, $claimsFile) = $this->getClaimsData($from, $to);

        $amount = [];
        $amount['claims'] = $claimAmount;
        $amount['refunds'] = $refundAmount;
        $amount['total'] = $claimAmount - $refundAmount;

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFile, $refundsFile);
        }

        return ['refunds' => $refundsFile, 'claims' => $claimsFile];
    }

    protected function getRefundsData($from, $to)
    {
        $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                                Payment\Entity::BANK, $this->bankCode, $from, $to, $this->gateway);

        $count = $refunds->count();

        if ($count == 0)
        {
            return [0, ''];
        }

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $payment->toArray();
            $col['terminal'] = $terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $terminal->getGateway();

        $action = 'generateRefunds';

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function getClaimsData($from, $to)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        $claims= $this->repo->payment->fetchPaymentsWithStatus($from, $to,
                                                               $this->gateway,
                                                               $status);

        if ($claims->count() === 0)
        {
            return [0, ''];
        }

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

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function sendMail($amount, $claimsFile, $refundsFile)
    {
        $bankName = $this->getBankName();

        $data = [
            'amount'      => $amount,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'bankName'    => $bankName,
        ];

        $dailyFileMail = new DailyFileMail($data);

        Mail::send($dailyFileMail);
    }

    protected function getBankName()
    {
        return ucfirst(explode('_', $this->gateway)[1]);
    }
}
