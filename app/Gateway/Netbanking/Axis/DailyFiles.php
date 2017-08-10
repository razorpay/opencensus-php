<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles extends Base\DailyFiles
{
    protected $emailIdsToSendTo = 'axis.netbanking.refunds@razorpay.com';

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
}
