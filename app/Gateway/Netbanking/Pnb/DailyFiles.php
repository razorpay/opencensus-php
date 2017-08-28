<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles extends Base\DailyFiles
{
    protected $emailIdsToSendTo = 'settlements@razorpay.com';

    public function generate($from, $to, $email = null)
    {
        $refundData = $this->getRefundsData($from, $to);

        $claimData = $this->getClaimsData($from, $to);

        $amount = [
            'claims'  => $claimData['total_amount'],
            'refunds' => $refundData['total_amount'],
            'total'   => $claimData['total_amount'] - $refundData['total_amount'],
        ];

        $count = [
            'claims'  => $claimData['count'],
            'refunds' => $refundData['count'],
            'total'   => $claimData['count'] + $refundData['count'],
        ];

        $claimsFile = [
            'url'  => $claimData['signed_url'],
            'name' => basename($claimData['local_file_path']),
        ];

        $refundsFile = [
            'url'  => $refundData['signed_url'],
            'name' => basename($refundData['local_file_path']),
        ];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail(
                $amount,
                $claimsFile,
                $refundsFile,
                $count,
                $email);
        }

        return [
            'refunds' => $refundData['local_file_path'],
            'claims'  => $claimData['local_file_path']
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

    protected function getClaimsData($from, $to)
    {
        $claims = [];

        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        $payments = $this->repo->payment
                               ->fetchPaymentsWithStatus($from,
                                                         $to,
                                                         $this->gateway,
                                                         $status);

        foreach ($payments as $payment)
        {
            $refundAmount = $payment[Payment\Entity::AMOUNT_REFUNDED];

            $amountToClaim = $payment[Payment\Entity::AMOUNT] - $refundAmount;

            // not to include payments where
            // full refund has happened
            if ($amountToClaim === 0)
            {
                continue;
            }

            $payment[Payment\Entity::AMOUNT] = $amountToClaim;
            $payment[Constants::CLAIM_TYPE]  = Constants::DEBIT;
            $payment[Constants::TXN_DETAIL]  = Constants::PAYMENT;

            $claims[] = $payment;
        }

        $refunds = $this->repo->refund
                              ->findBetweenTimestamps(strtotime('-1 day', $from),
                                                      strtotime('-1 day', $to));

        foreach ($refunds as $refund)
        {
            $refund[Payment\Entity::ID]    = $refund->getPaymentId();
            $refund[Constants::CLAIM_TYPE] = Constants::CREDIT;
            $refund[Constants::TXN_DETAIL] = Constants::REFUND;

            $claims[] = $refund;
        }

        if (count($claims) === 0)
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

        $action = 'generateClaims';

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }
}
