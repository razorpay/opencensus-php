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
        $yesterdayVolume = $this->repo->payment->getYesterdayVolume();

        $monthVolume = $this->repo->payment->getCurrentMonthVolume();

        $yesterdayMerchantVolume = $this->repo->payment->getYesterdayTopMerchantVolumeWise();

        $monthlyMerchantVolume = $this->repo->payment->getMonthTopMerchantVolumeWise();

        $message = '
            Yesterday Volume        - ' . $yesterdayVolume->getAttribute('amount') / 100 . ' <br />
            Monthly Volume till now - ' . $monthVolume->getAttribute('amount') / 100 . ' <br /><br />';


        $message .= '
            Yesterday Transactions count        - ' . $yesterdayVolume->getAttribute('count') . ' <br />
            Monthly Transactions count till now - ' . $monthVolume->getAttribute('count') . ' <br /><br />';

        $message .= 'Yesterday Top Merchants By Volume - <br />';
        $message .= $this->getTabularFormattedMerchantVolumeScorecard($yesterdayMerchantVolume);

        $message .= 'Monthly Top Merchants By Volume - <br />';
        $message .= $this->getTabularFormattedMerchantVolumeScorecard($monthlyMerchantVolume);

        $data['body'] = $message;

        $scoreCardMail = new ScorecardMail($data);

        Mail::send($scoreCardMail);

        return ['success' => true];
    }

    protected function getTabularFormattedMerchantVolumeScorecard($volumeData)
    {
        $message = '<table border="1">';

        $message .= '<tr>' .
                    '<th> Merchant Id </th>'.
                    '<th> Name </th>'.
                    '<th> Website </th>'.
                    '<th> Volume </th>'.
                    '<th> Count </th>'.
                    '</tr>';

        foreach ($volumeData as $merchantData)
        {
            $message .= '<tr>';

            $attributes = $merchantData->getAttributes();

            foreach ($attributes as $key => $value)
            {
                $message .= '<td>' . $value . '</td>';
            }

            $message .= '</tr>';
        }

        $message .= '</table><br />';

        return $message;
    }
}
