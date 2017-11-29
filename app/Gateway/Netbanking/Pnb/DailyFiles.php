<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
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
        if ($count['claims'] + $count['refunds'] > 0)
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

    /**
     * We need to send them all the payments where
     * amount = amount - amount_refunded
     * And all the refunds that happened at T-1
     * but the payments were authorized before T-1
     *
     * @param integer $from
     * @param integer $to
     */
    protected function getClaimsData($from, $to)
    {
        $claims = [];

        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
        ];

        $payments = $this->repo->payment
                               ->fetchPaymentsWithStatus($from,
                                                         $to,
                                                         $this->gateway,
                                                         $status);

        foreach ($payments as $payment)
        {
            $amountToClaim = $payment[Payment\Entity::AMOUNT] - $payment[Payment\Entity::AMOUNT_REFUNDED];

            $payment[Payment\Entity::AMOUNT] = $amountToClaim;
            $payment[Constants::CLAIM_TYPE]  = Constants::DEBIT;
            $payment[Constants::TXN_DETAIL]  = Constants::PAYMENT;

            $claims[] = $payment;
        }

        $refunds = $this->repo->refund
                              ->fetchRefundsForPnbClaims($from, $to, $this->gateway);

        foreach ($refunds as $refund)
        {
            $refund[Refund\Entity::ID]     = $refund['payment']['id'];
            $refund[Constants::CLAIM_TYPE] = Constants::CREDIT;
            $refund[Constants::TXN_DETAIL] = Constants::REFUND;
            $refund['terminal']            = $refund['payment']['terminal'];

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

        return $this->app['gateway']->call($gateway, Payment\Action::GENERATE_CLAIMS, $input, $this->mode);
    }
}
