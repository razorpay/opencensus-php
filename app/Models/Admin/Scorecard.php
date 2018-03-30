<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use Mail;
use RZP\Exception;
use RZP\Mail\Admin\Scorecard as ScorecardMail;
use RZP\Models;
use RZP\Models\Base;
use RZP\Constants\MailTags;

class Scorecard extends Base\Core
{
    public function generateScorecard($input)
    {
        (new Validator)->validateInput('scorecard', $input);

        $limit = $input['count'];

        $yesterdayVolume = $this->repo->payment->getYesterdayVolume();

        $monthVolume = $this->repo->payment->getCurrentMonthVolume();

        $yesterdayMerchantVolume = $this->repo->payment->getYesterdayTopMerchantVolumeWise($limit);

        $monthlyMerchantVolume = $this->repo->payment->getMonthTopMerchantVolumeWise($limit);

        $data =  [
            'yesterdayVolume'         => $yesterdayVolume,
            'monthVolume'             => $monthVolume,
            'yesterdayMerchantVolume' => $yesterdayMerchantVolume,
            'monthlyMerchantVolume'   => $monthlyMerchantVolume
        ];

        $scoreCardMail = new ScorecardMail($data);

        Mail::queue($scoreCardMail);

        return ['success' => true];
    }
}
