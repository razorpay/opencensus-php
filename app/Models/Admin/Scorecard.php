<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Entity;
use RZP\Models\Base;
use RZP\Models;
use RZP\Exception;

class Scorecard extends Base\Core
{
    public function generateScorecard($input)
    {
        $yesterdayVolume = $this->repo->payment->getYesterdayVolume();

        $monthVolume = $this->repo->payment->getCurrentMonthVolume();

        $yesterdayMerchantVolume = $this->repo->payment->getYesterdayTopMerchantVolumeWise();

        $message = '
            Yesterday Volume        - ' . $yesterdayVolume . ' <br />
            Monthly Volume till now - ' . $monthVolume . ' <br />
            Yesterday Top Merchants By Volume - <br />';

        $pads = [14, 100, 80, 12, 5];

        $message =  str_pad('MerchantId', $pads[0]) .
                    str_pad('Name', $pads[1]) .
                    str_pad('Website', $pads[2]) .
                    str_pad('Volume', $pads[3]) .
                    str_pad('Count', $pads[4]);

        foreach ($yesterdayMerchantVolume as $m)
        {
            $message .=
                str_pad($m['merchant_id'], $pads[0]) .
                str_pad($m['name'], $pads[1]) .
                str_pad($m['website'], $pads[2]) .
                str_pad($m['volume'], $pads[3]) .
                str_pad($m['count'], $pads[4]);
        }

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
