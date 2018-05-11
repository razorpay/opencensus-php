<?php

namespace App\Admin;

use Auth;
use Trace;
use App\MerchantDetails;
use App\Trace\TraceCode;

trait Logger
{
    public function logDataExport($entity, $params)
    {
        $adminId = Auth::guard('api')->user()->username;

        //$this->slackPost("Data export by $adminId ($entity)", $params, '#tech_logs');

        $traceData = ['admin' => $adminId, 'params' => $params, 'channel' => 'tech_logs'];

        Trace::info(TraceCode::SLACK_DATA_EXPORT_LOG, $traceData);
    }

    /**
     * Returns markdown
     * @param  [type] $id MerchantId [description]
     * @return [type]           [description]
     */
    protected function getMerchantDashboardSlackText($id)
    {
        $label = $this->getBillingLabel($id);

        $link = "https://dashboard.razorpay.com/admin/merchants/$id";

        return "<$link|$label> ($id)";
    }

    protected function getBillingLabel($merchantId)
    {
        $merchantDetails = (new MerchantDetails\Service)->fetchDetails($merchantId);

        return $merchantDetails['business_dba'];
    }

    /**
     * This does not log to slack due to slack version issue, refer https://github.com/razorpay/dashboard/pull/2426
     * We just trace logs here for now. TODO: Add slack log after using correct razorpay/slack-laravel version
     *
     * @param $merchantId
     * @param $action
     * @param array $data
     */
    protected function logActionToSlack($merchantId, $action, $data = [])
    {
        $adminId = Auth::guard('api')->user()->username;

        $text = $this->getMerchantDashboardSlackText($merchantId);

        $text .= " $action by $adminId";

        $channel = $this->getChannel($action);

        $data = $this->flatten($data);

        $color = 'good';

        if (isset($data['risk_rating']) and $data['risk_rating'] > 3)
        {
            // 4 is high, 5 is very high
            $color = 'danger';
        }

        //$this->slackPost($text, $data, $channel, '', $color);

        $traceData = array_merge($data, ['admin' => $adminId, 'action' => $action, 'merchant_id' => $merchantId]);

        Trace::info(TraceCode::ADMIN_ACTION_SLACK_LOG, $traceData);
    }

    protected function getChannel($action)
    {
        switch ($action) {
            case Actions::ACTIVATED:
                return \Config::get('razorpay.slack.activations');
                break;

            case Actions::FUNDS_HELD:
            case Actions::FUNDS_RELEASED:
            case Actions::RISK_RATING_CHANGED:
                return \Config::get('razorpay.slack.risk');

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

        //$this->slackPost($text, [], $postChannel);

        $traceData = ['user' => $user, 'query' => $linkText, 'channel' => $channel];

        Trace::info(TraceCode::SLACK_QUERY_LOG, $traceData);
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
