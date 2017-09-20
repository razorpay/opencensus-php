<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles extends Base\DailyFiles
{
    protected $emailIdsToSendTo = 'rbl.netbanking.refunds@razorpay.com';

    public function generate($from, $to, $email = null)
    {
        $refundsData = $this->getRefundsData($from, $to);

        $claimsData = $this->getClaimsData($from, $to);

        $amount = [
            'claims'  => $claimsData['total_amount'],
            'refunds' => $refundsData['total_amount'],
            'total'   => $claimsData['total_amount'] - $refundsData['total_amount'],
        ];

        $count = [
            'claims'  => $claimsData['count'],
            'refunds' => $refundsData['count'],
        ];

        $refundsFile = [
            'url'  => $refundsData['signed_url'],
            'name' => basename($refundsData['local_file_path']),
        ];

        $claimsFile = [
            'url'  => $claimsData['signed_url'],
            'name' => basename($claimsData['local_file_path']),
        ];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail(
                $amount,
                $claimsFile,
                $refundsFile,
                $count,
                $email
            );
        }

        if (($amount['claims'] - $amount['refunds']) < 0)
        {
            $message = 'Claims Total Amount for RBL is less than Refund Amount';

            $data = [
                'refundsData' => $refundsData,
                'claimsData'  => $claimsData,
            ];

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.settlements'),
                ]
            );
        }

        return [
            'refunds' => $refundsData['local_file_path'],
            'claims'  => $claimsData['local_file_path'],
        ];
    }

    protected function sendMail($amount, $claimsFile, $refundsFile, $count = [], $email = null)
    {
        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        $bankName = $this->getBankName();

        $emails = $this->getEmailsToSendTo($email);

        $data = [
            'bankName'    => $bankName,
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'emails'      => $emails,
        ];

        $dailyFileMail = new DailyFileMail($data);

        Mail::queue($dailyFileMail);
    }

    /**
     * This is done because we just want
     * to pick up reconciled payments
     */
    protected function getClaimsData($from, $to)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        // Payments made yesterday are reconciled today,
        // so forwarding time stamps by 1 day
        list($from, $to) = $this->updateTimeStamps($from, $to);

        $claims= $this->repo->payment
                            ->fetchReconciledPaymentsForGateway($from,
                                                                $to,
                                                                $this->gateway,
                                                                $status);

        if ($claims->count() === 0)
        {
            return [
                'total_amount'    => 0,
                'count'           => 0,
                'url'             => '',
                'name'            => '',
                'local_file_path' => '',
                'signed_url'      => '',
                'file_name'       => '',
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

        $gateway = $this->gateway;

        return $this->app['gateway']->call($gateway, Payment\Action::GENERATE_CLAIMS, $input, $this->mode);
    }

    protected function updateTimeStamps($from, $to)
    {
        $tsDifference = self::SECONDS_PER_DAY;

        return [$from + $tsDifference, $to + $tsDifference];
    }
}
