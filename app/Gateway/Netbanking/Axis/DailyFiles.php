<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use Mail;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

class DailyFiles extends Base\DailyFiles
{
    public function generate($from, $to)
    {
        list($refundAmount, $refundsFile) = $this->getRefundsData($from, $to);

        list($claimAmount, $claimsFile) = $this->getClaimsData($from, $to);

        $amount = [];
        $amount['claims'] = $claimAmount;
        $amount['refunds'] = $refundAmount;
        $amount['total'] = $claimAmount - $refundAmount;

        $count = [];

        $count['claims'] = empty($claimsFile) ? 0 : count(file($claimsFile)) - 1;

        $count['refunds'] = empty($refundsFile) ? 0 : count(file($refundsFile)) - 1;

        $count['total'] = $count['claims'] + $count['refunds'];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFile, $refundsFile, $count);
        }

        return ['refunds' => $refundsFile, 'claims' => $claimsFile];
    }

    protected function sendMail(array $amount, string $claimsFile, string $refundsFile, array $count = [])
    {
        $date = Carbon::now('Asia/Kolkata')->format('jS F Y');

        $bankName = $this->getBankName();

        $data = [
            'bankName'    => $bankName,
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile
        ];

        $dailyFileMail = new DailyFileMail($data);

        Mail::queue($dailyFileMail);
    }
}
