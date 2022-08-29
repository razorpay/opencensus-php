<?php

namespace App\Base;

use Auth;
use Slack;
use Config;
use App\RZP\Api;
use Razorpay\Api\Request as ApiRequest;

class Service
{
    const ACCOUNT_HEADER = 'X-Razorpay-Account';

    private function setHeaders()
    {

        $requestedClientIPS = \Request::ips();

        $clientIp = end($requestedClientIPS);

        ApiRequest::addHeader('X-Dashboard', 'true');
        ApiRequest::addHeader('X-User-Agent', \Request::header('User-Agent'));
        ApiRequest::addHeader('X-IP-Address', \Request::ip());
        ApiRequest::addHeader('X-Dashboard-Ip', $clientIp);

        $user = Auth::guard('user')->user();

        if ($user)
        {
            ApiRequest::addHeader('X-Dashboard-User-Id', $user->id);
            ApiRequest::addHeader('X-Dashboard-User-Email', $user->email);

            $currentMerchant = $user->currentMerchant();

            if ($currentMerchant !== null)
            {
                ApiRequest::addHeader('X-Dashboard-User-Role', $currentMerchant->role);
            }
        }
        else if (app('request.ctx')->isOauthRequest() === true)
        {
            ApiRequest::addHeader('X-Dashboard-User-Id', app('request.ctx')->getUserId());
        }
    }

    public function setAdminCredentials($merchant_id = null, $mode = 'live')
    {
        $token = Auth::guard('api')->user()->token;

        ApiRequest::addHeader('X-Admin-Token', $token);

        $this->setApiCredentials($merchant_id, $mode);
    }

    public function setApiCredentials($merchant_id = null, $mode = 'live')
    {
        $this->setHeaders();

        $id = 'rzp_' . $mode;

        if ($merchant_id)
        {
            $id = $id . '_' . $merchant_id;
        }

        $secret = Config::get('api.auth_pass');

        $this->api = new Api($id, $secret);
    }

    public function setApiCredentialsForPublicAuth($key)
    {
        ApiRequest::addHeader('X-Dashboard', 'true');

        $this->api = new Api($key, null);
    }

    public function setAccountCredentials(string $accountId)
    {
        ApiRequest::addHeader(self::ACCOUNT_HEADER, $accountId);
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

    protected function getS3Client($version = '')
    {
        $config = config('aws');

        if($version === 'v2')
        {
            $config['region'] = config('aws.migrated_bucket_region');
        }
        else
        {
            $config['region'] = config('aws.bucket_region');
        }

        $client = new \Aws\Sdk($config);

        return $client->createClient('S3');
    }

    protected function stripSign(string & $entityId)
    {
        $matches = null;

        preg_match('/([a-zA-Z0-9]{14})/', $entityId, $matches);

        $entityId = $matches[1];
    }
}
