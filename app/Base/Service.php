<?php

namespace App\Base;

use Razorpay\Api\Request as ApiRequest;
use Config;
use App\RZP\Api;
use Slack;

class Service
{
    public function setApiCredentials($merchant_id = null, $mode = 'live')
    {
        ApiRequest::addHeader('X-Dashboard', 'true');
        $id = 'rzp_' . $mode;

        if ($merchant_id)
        {
            $id = $id . '_' . $merchant_id;
        }

        $secret = Config::get('api.auth_pass');

        $this->api = new Api($id, $secret);
    }

    public function slackPost($headline, $postdata, $channel, $pretext = '', $color = 'good')
    {
        if (config('slack.enable'))
        {
            $data = array();
            $data['fallback'] = $headline.'\n';
            $data['fields'] = array();
            $data['color'] = $color;
            $data['pretext'] = $pretext;
            $data['link_names'] = 1;

            /**
             * Attach all the extra fields
             */
            foreach($postdata as $key => $value)
            {
                //  Fallback text for plaintext clients, like IRC
                $data['fallback'] .= $key . ': ' . $value . '\n';
                $data['fields'][] = array(
                    'title' => $key,
                    'value' => $value,
                    'short' => true
                );
            }

            Slack::to($channel)->attach($data)->queue($headline);
        }
    }

    /**
     * Return the entity from the API if it exists
     * @param  string $entity Entity to fetch
     * @param  string $id     Entity Id
     * @return \Razorpay\Api\Entity | null
     */
    public function fetchApiEntityIfExists($entity, $id)
    {
        try
        {
            return $this->api->$entity->fetch($id);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return null;
        }

        return null;
    }
}
