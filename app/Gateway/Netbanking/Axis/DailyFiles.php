<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;

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

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail(
                $amount,
                $claimData['signed_url'],
                $refundData['signed_url'],
                $count,
                $email);
        }

        return [
            'refunds' => $refundData['local_file_path'],
            'claims' => $claimData['local_file_path']
        ];
    }

    protected function sendMail($amount, $claimsFile, $refundsFile, $count=[], $email = null)
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

        $emails = $this->getEmailsToSendTo($email);

        $this->mail->queue($view, $data, function($message) use ($data, $bankName, $emails)
        {
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
