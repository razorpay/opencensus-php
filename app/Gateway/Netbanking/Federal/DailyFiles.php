<?php

namespace RZP\Gateway\Netbanking\Federal;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{
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
        $today = Carbon::now('Asia/Kolkata')->format('d/m/Y');

        $bankName = $this->getBankName();

        $view = 'emails.admin.' . lcfirst($bankName) . '_refunds';

        $emails = $this->getEmailsToSendTo($email);

        $data = [
            'subject'     => $bankName . ' Netbanking claims and refund files for ' . $today,
            'amount'      => $amount,
            'count'       => $count,
            'emails'      => $emails,
            'date'        => $today,
            'refundsFile' => $refundsFile
        ];

        $this->mail->queue($view, $data, function($message) use ($data, $bankName, $emails)
        {
            $message->from('refunds@razorpay.com', $bankName . ' Netbanking Refunds');

            $message->subject($data['subject']);

            $message->to($emails);

            if (empty($data['refundsFile']) === false)
            {
                $message->attach($data['refundsFile']['url'], ['as' => $data['refundsFile']['name']]);
            }

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::FEDERAL_NETBANKING_REFUNDS_MAIL);
        });
    }
}
