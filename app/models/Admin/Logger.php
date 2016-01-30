<?php

namespace Models\Admin;

use Auth;
use Models\Merchant\Entity as MerchantEntity;

trait Logger
{
    public function logDataExport($entity, $params)
    {
        $adminId = Auth::admin()->get()->username;

        $this->slackPost("Data export by $adminId ($entity)", $params, '#tech_logs');
    }

    /**
     * Returns markdown
     * @param  [type] $merchant [description]
     * @return [type]           [description]
     */
    protected function getMerchantDashboardSlackText($merchant)
    {
        $id = $merchant->id;
        $link = "https://dashboard.razorpay.com/admin#/app/merchants/$id/detail";

        $label = $merchant->merchantDetails->getBillingLabel();

        return "<$link|$label>";
    }

    protected function logActionToSlack($merchant, $action, $data = [])
    {
        // We were passed a merchant id
        if (is_string($merchant))
        {
            $merchant = MerchantEntity::find($merchant);
        }
        if (! $merchant)
        {
            return false;
        }

        $adminId = Auth::admin()->get()->username;

        $text = $this->getMerchantDashboardSlackText($merchant);
        $text .= " $action by $adminId";

        $channel = \Config::get('razorpay.slack.operations');

        $this->slackPost($text, $data, $channel);
    }
}
