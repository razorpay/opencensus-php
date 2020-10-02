<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use Mail;
use RZP\Exception;
use RZP\Mail\Admin\Scorecard as ScorecardMail;
use RZP\Models;
use RZP\Models\Base;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;

class Scorecard extends Base\Core
{
    public function generateScorecard($input)
    {
        (new Validator)->validateInput('scorecard', $input);

        $limit = $input['count'];

        $yesterdayVolume = $this->getYesterdayVolume();

        $monthVolume = $this->getCurrentMonthVolume();

        $yesterdayMerchantVolume = $this->getYesterdayTopMerchantVolumeWise($limit);

        // $monthlyMerchantVolume = $this->getMonthlyTopMerchantVolumeWise($limit);

        $data =  [
            'yesterdayVolume'         => $yesterdayVolume,
            'monthVolume'             => $monthVolume,
            'yesterdayMerchantVolume' => $yesterdayMerchantVolume,
            //'monthlyMerchantVolume'   => $monthlyMerchantVolume
        ];

        $scoreCardMail = new ScorecardMail($data);

        //
        // This mail is huge (more than 256KB) and breaches SQS message payload.
        // Think twice before cleverly changing it to queue, as was attempted
        // previously here https://github.com/razorpay/api/pull/7734/files#diff-6a90a4767da4f4cac0e507d0cf686d6dL34
        //
        Mail::send($scoreCardMail);

        return ['success' => true];
    }

    private function getYesterdayVolume()
    {
        list($from, $to) = $this->getYesterdayTimestamps();

        return $this->repo->payment->getPaymentVolumeBetweenTimestamp($from, $to);
    }

    private function getCurrentMonthVolume()
    {
        list($from, $end) = $this->getMonthTimestamps();

        $monthlyAmountVol = 0;

        $monthlyCount = 0;

        while ($from < $end)
        {
            $to = $from + 86400*5;

            $to = min($to, $end);

            $volume = $this->repo->payment->getPaymentVolumeBetweenTimestamp($from, $to);

            $monthlyAmountVol += (int) $volume['amount'];

            $monthlyCount += (int) $volume['count'];

            $from = $to;
        }

        return [
            'amount' => $monthlyAmountVol,
            'count'  => $monthlyCount
        ];
    }

    private function getYesterdayTopMerchantVolumeWise(int $limit)
    {
        list($from, $to) = $this->getYesterdayTimestamps();

        return $this->repo->payment->getTopMerchantVolumeWiseBetweenTimestamp(
            $from, $to, $limit);
    }

    private function getMonthlyTopMerchantVolumeWise(int $limit)
    {
        list($from, $to) = $this->getMonthTimestamps();

        return $this->repo->payment->getTopMerchantVolumeWiseBetweenTimestamp(
            $from, $to, $limit);
    }

    private function getYesterdayTimestamps()
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp();

        return [$from, $to];
    }

    private function getMonthTimestamps()
    {
        $from = Carbon::yesterday(Timezone::IST)->startOfMonth()->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp();

        return [$from, $to];
    }
}
