<?php

namespace App\Base;

use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\BadRequestError;
use Config;
use App\RZP\Api;
use Slack;
use Auth;

class Service
{
    const ACCOUNT_HEADER = 'X-Razorpay-Account';

    private function setHeaders()
    {
        ApiRequest::addHeader('X-Dashboard', 'true');
        ApiRequest::addHeader('X-User-Agent', \Request::header('User-Agent'));
        ApiRequest::addHeader('X-IP-Address', \Request::ip());
    }

    public function setAdminCredentials($mode = 'live')
    {
        $token = Auth::guard('api')->user()->token;

        ApiRequest::addHeader('X-Admin-Token', $token);

        $this->setApiCredentials(null, $mode);
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

    protected function getS3Client()
    {
        $config = config('aws');

        $config['region'] = config('aws.bucket_region');

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
