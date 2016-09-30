<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Entity;
use RZP\Models\Base;
use RZP\Models;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Scorecard extends Base\Core
{
    public function generateScorecard($input)
    {
        $yesterdayVolume = $this->repo->payment->getYesterdayVolume();

        $monthVolume = $this->repo->payment->getCurrentMonthVolume();

        $yesterdayMerchantVolume = $this->repo->payment->getYesterdayTopMerchantVolumeWise();

        $message = '
            Yesterday Volume        - ' . $yesterdayVolume / 100 . ' <br />
            Monthly Volume till now - ' . $monthVolume / 100 . ' <br />
            Yesterday Top Merchants By Volume - <br />';

        $message .= '<table border="1">';

        $message .= '<tr>' .
                    '<th> Merchant Id </th>'.
                    '<th> Name </th>'.
                    '<th> Website </th>'.
                    '<th> Volume </th>'.
                    '<th> Count </th>'.
                    '</tr>';

        foreach ($yesterdayMerchantVolume as $merchantData)
        {
            $message .= '<tr>';

            $attributes = $merchantData->getAttributes();

            foreach ($attributes as $key => $value)
            {
                $message .= '<td>' . $value . '</td>';
            }

            $message .= '</tr>';
        }

        $message .= '</table>';

        $data['body'] = $message;

        Mail::send('emails.message', $data, function($message)
        {
            $emails = ['scorecard@razorpay.com'];

            $message->from('scorecard@razorpay.com', 'Razorpay Scorecard');

            $dt = Carbon::today('Asia/Kolkata')->format('d-m-y');

            $subject = 'Razorpay | Scorecard for ' . $dt;

            $message->subject($subject);

            $message->to($emails);
        });

        return ['success' => true];
    }
}
