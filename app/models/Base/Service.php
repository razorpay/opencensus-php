<?php

namespace Models\Base;

use Config;
use RZP\Api;
use Slack;

class Service
{
    public function setApiCredentials($merchant_id = null, $mode = 'live')
    {
        $id = 'rzp_'.$mode;

        if ($merchant_id)
        {
            $id = $id.'_'.$merchant_id;
        }

        $secret = Config::get('api.auth_pass');

        $this->api = new Api($id, $secret);
    }

    public function slackPost($headline, $postdata, $channel, $pretext = '', $color = 'good')
    {
        if($_ENV['SLACK_ENABLE'] === true)
        {
            $data = array();
            $data['fallback'] = $headline.'\n';
            $data['fields'] = array();
            $data['color'] = $color;
            $data['pretext'] = $pretext;
            $data['link_names'] = 1;
            foreach($postdata as $key => $value)
            {
                $data['fallback'] .= $key . ': ' . $value . '\n';
                $data['fields'][] = array(
                    'title' => $key,
                    'value' => $value,
                    'short' => false
                );
            }

            Slack::to($channel)->attach($data)->send($headline);
        }
    }
}
