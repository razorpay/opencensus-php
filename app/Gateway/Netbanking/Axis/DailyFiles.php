<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;

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

        $count['claims'] = empty($claimsFile) ? 0 : count(file($claimsFile))-1;

        $count['refunds'] = empty($refundsFile) ? 0 : count(file($refundsFile))-1;

        $count['total'] = $count['claims'] + $count['refunds'];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFile, $refundsFile, $count);
        }

        return ['refunds' => $refundsFile, 'claims' => $claimsFile];
    }

    protected function sendMail($amount, $claimsFile, $refundsFile, $count=[])
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $date = Carbon::now('Asia/Kolkata')->format('jS F Y');

        $bankName = $this->getBankName();

        $data = [
            'subject'     => $bankName . ' Netbanking claims and refund files for ' . $today,
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile
        ];

        $view = 'emails.admin.' . lcfirst($bankName) . '_refunds';

        $this->mail->queue($view, $data, function($message) use ($data, $bankName)
        {
            $emails = ['axis.netbanking.refunds@razorpay.com'];

            $message->from('refunds@razorpay.com', $bankName . ' Netbanking Refunds');

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

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::AXIS_NETBANKING_REFUNDS_MAIL);
        });
    }
}
