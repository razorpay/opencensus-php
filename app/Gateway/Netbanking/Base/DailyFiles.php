<?php

namespace RZP\Gateway\Netbanking\Base;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Refund;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles
{
    protected $app;
    protected $repo;
    protected $mode;
    protected $gateway;
    protected $bankCode;
    protected $refundCore;

    // SECONDS_PER_DAY is 24 Hours/Day * 60 Minutes/Hour * 60 Seconds/Minute
    //                 is 86400
    const SECONDS_PER_DAY = 86400;

    protected $emailIdsToSendTo = 'settlements@razorpay.com';

    public function __construct($bankCode)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->mode = $this->app['basicauth']->getMode();

        $this->gateway = Payment\Gateway::$netbankingToGatewayMap[$bankCode];

        $this->bankCode = $bankCode;

        $this->refundCore = new Refund\Core;
    }

    public function generate($from, $to, $email = null)
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
            $this->sendMail($amount, $claimsFile, $refundsFile, $email);
        }

        return ['refunds' => $refundsFile, 'claims' => $claimsFile];
    }

    protected function getRefundsData($from, $to)
    {
        $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                                            Payment\Entity::BANK, $this->bankCode, $from, $to, $this->gateway);

        $count = $refunds->count();

        if ($count === 0)
        {
            return [
                'total_amount'   => 0,
                'count'          => 0,
                'signed_url'     => '',
                'local_file_path' => '',
            ];
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

        $refundsResult = $this->app['gateway']->call($gateway, Payment\Action::GENERATE_REFUNDS, $input, $this->mode);

        $this->refundCore->reconcileNetbankingRefunds($data);

        return $refundsResult;

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
            return [
                'total_amount'   => 0,
                'count'          => 0,
                'signed_url'     => '',
                'local_file_path' => '',
            ];
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

        return $this->app['gateway']->call($gateway, Payment\Action::GENERATE_CLAIMS, $input, $this->mode);
    }

    protected function sendMail($amount, $claimsFileData, $refundsFileData, $email = null)
    {
        $bankName = $this->getBankName();

        $emails = $this->getEmailsToSendTo($email);

        $data = [
            'amount'      => $amount,
            'claimsFile'  => $claimsFileData,
            'refundsFile' => $refundsFileData,
            'bankName'    => $bankName,
            'emails'      => $emails,
        ];

        $dailyFileMail = new DailyFileMail($data);

        Mail::queue($dailyFileMail);
    }

    protected function getBankName()
    {
        return ucfirst(explode('_', $this->gateway)[1]);
    }

    protected function getEmailsToSendTo($email = null)
    {
        $returnEmail = $this->emailIdsToSendTo;

        if (empty($email) === false)
        {
            $returnEmail = $email;
        }

        return [$returnEmail];
    }
}
