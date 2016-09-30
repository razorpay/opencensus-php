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
        $yesterdayMerchantVolume = $yesterdayVolume->toArray();

        $this->trace->info(TraceCode::MISC_TRACE_CODE, $yesterdayMerchantVolume);

        $message = '
            Yesterday Volume        - ' . $yesterdayVolume . ' <br />
            Monthly Volume till now - ' . $monthVolume . ' <br />
            Yesterday Top Merchants By Volume - <br />';

        // Padding for each column
        $pads = ['merchant_id' => 14, 'name' => 100, 'website' => 80, 'volume' => 12, 'count' => 5];

        $message .= str_pad('MerchantId', $pads['merchant_id']) .
                    str_pad('Name', $pads['name']) .
                    str_pad('Website', $pads['website']) .
                    str_pad('Volume', $pads['volume']) .
                    str_pad('Count', $pads['count']);

        foreach ($yesterdayMerchantVolume as $m)
        {
            $message .= '<br />';

            foreach ($m as $key => $value)
            {
                $message .= str_pad($m[$key], $pads[$key]);
            }
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
