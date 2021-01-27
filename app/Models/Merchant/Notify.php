<?php

namespace RZP\Models\Merchant;

use RZP\Models\Merchant;

trait Notify
{
    /**
     * Returns markdown
     * @param  [type] $merchant [description]
     * @return [type]           [description]
     */
    protected function getMerchantDashboardSlackText($merchant)
    {
        $id = $merchant->id;

        $label = $merchant->billing_label;

        $link = $this->app->config->get('applications.dashboard.url')."admin#/app/merchants/$id/detail";

        return "<$link|$label> ($id)";
    }

    protected function logActionToSlack($merchant, $action, $data = [], $link = '')
    {
        $messageUser = Constants::DASHBOARD_INTERNAL;

        $admin = (new Merchant\Core)->getInternalUsernameOrEmail();

        if($admin === Constants::DASHBOARD_INTERNAL)
        {
            $messageUser = Constants::MERCHANT_USER;
        }

        if (empty($link) === true)
        {
            $text = $this->getMerchantDashboardSlackText($merchant);
        }
        else
        {
            $text = $link;
        }

        $textAction = SlackActions::$actionMsgMap[$action];

        $text .= " $textAction by $messageUser";

        $channel = $this->getChannel($action);

        $data = $this->flatten($data);

        $color = 'good';

        if (isset($data['risk_rating']) and $data['risk_rating'] > 3)
        {
            // 4 is high, 5 is very high
            $color = 'danger';
        }

        $this->slackPost($text, $data, $channel, '', $color);
    }

    protected function slackPost($headline, $postData, $channel, $pretext = '', $color = 'good')
    {
        if ($this->app->config->get('slack.is_slack_enabled') === true)
        {
            $settings = [];
            $settings['color'] = $color;
            $settings['pretext'] = $pretext;
            $settings['link_names'] = 1;
            $settings['channel'] =$channel;

            $this->app['slack']->queue($headline, $postData, $settings);
        }
    }

    protected function getChannel($action)
    {
        switch ($action) {
            case SlackActions::FUNDS_HELD:
            case SlackActions::FUNDS_RELEASED:
            case SlackActions::RISK_RATING_CHANGED:
                return $this->app->config->get('slack.channels.risk');

            default:
                return $this->app->config->get('slack.channels.operations');
                break;
        }
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
        $result = [];

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $this->flatten($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
