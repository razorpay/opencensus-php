<?php

namespace RZP\Gateway\Netbanking\Federal;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles extends Base\DailyFiles
{
    protected $emailIdsToSendTo = 'federal.netbanking.refunds@razorpay.com';

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
            'name' => basename($refundsData['local_file_path'])
        ];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail(
                $amount,
                null,
                $refundsFile,
                $count,
                $email
            );
        }

        return ['refunds' => $refundsData['local_file_path']];
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
