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

        return "<$link|$label> ($id)";
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

        $channel = $this->getChannel($action);

        $data = $this->flatten($data);

        $this->slackPost($text, $data, $channel);
    }

    protected function getChannel($action)
    {
        switch ($action) {
            case 'activated':
                return \Config::get('razorpay.slack.activations');
                break;

            default:
                return \Config::get('razorpay.slack.operations');
                break;
        }
    }

    public function logSlackQuery($user, $entity, $channel)
    {
        $label = "{$entity['entity']}:{$entity['id']}";
        $linkText = Slack::getFormattedLinkForSlack($entity['entity'], $entity['id'], $label);

        $text = "@$user queried $linkText in #$channel";

        $postChannel = \Config::get('razorpay.slack.operations');

        $this->slackPost($text, [], $postChannel);
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     * @param  array $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    protected function flatten(array $array, $prefix = '')
    {
        $result = array();

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $result + $this->flatten($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
