<?php

namespace RZP\Gateway\Netbanking\Bob;

use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{
    protected $email = 'bob.netbanking.refunds@razorpay.com';

    public function generate($from, $to, $email = null)
    {
        $email = (($email === null) ? $this->email : $email);

        $file = $this->generateMail($from, $to, $email);

        return [
            'refunds' => $file['refunds'],
            'claims' => $file['claims'],
        ];
    }

    public function generateMail($from, $to, $tpvEnabled = false, $email = null)
    {
        $claimsFileData = $this->getClaimsDataForTpv($from, $to, $tpvEnabled);

        $refundFileData = $this->getRefundsDataForTpv($from, $to, $tpvEnabled);

        $amount = [];
        $amount['claims'] = $claimsFileData['total_amount'];
        $amount['refunds'] = $refundFileData['total_amount'];
        $amount['total'] = $claimsFileData['total_amount'] - $refundFileData['total_amount'];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFileData, $refundFileData, $email);
        }

        return [
            'claims' => $claimsFileData['local_file_path'],
            'refunds' => $refundFileData['local_file_path']
        ];
    }
}