<?php

namespace RZP\Models\Upi;

use Cache;
use RZP\Constants\Mode;
use RZP\Models\Customer\Service as CustomerService;
use RZP\Models\Device;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function callUpiGateway($method, array $gatewayData = [])
    {
        $gateway = 'upi_npci';
        try
        {
            $response = $this->app['gateway']->call($gateway, $method, $gatewayData, $this->mode);

            return ['success'=>true, 'txnId'=>$response['txn_id'], 'msgId' => $response['msg_id']];
        }
        catch (\Exception $ex)
        {
            return ['success'=>false, 'msg' => $ex->getMessage()];
        }
    }

    public function handleUPIRequest($api, $id, $body)
    {
        \Trace::info('GATEWAY_PAYMENT_CALLBACK', func_get_args());

        $parsedRequest = $this->app['upi.client']->parse($body, $api);

        $params = compact('api', 'id', 'body', 'parsedRequest');

        $response = $this->app['gateway']->call('upi_npci', 'handleRequest', $params, 'test');

        $this->app['trace']->info('GATEWAY_RESPONSE', $response);

        $this->cacheResponse($response);

        if ($response['queue'])
        {
            $this->pushToQueue($response['job'], $response['params']);
        }

        $ackXML = $this->app['gateway']->call('upi_npci', 'generateAckResponse', $params, 'test');

        return $ackXML;
    }

    protected function cacheResponse($response)
    {
        if (!isset($response['params']))
        {
            return;
        }

        $params = $response['params'];

        // We cache stuff!
        if (isset($params['cacheKey']))
        {
            Cache::forever($params['cacheKey'], $params['cacheValue']);
        }
    }

    protected function updateKeyStore($params)
    {
        // We update the device token here
        if (isset($params['token']) and isset($params['device_id']))
        {
            $device = (new Device\Service)->updateUpiToken($params['device_id'], $params['token']);
        }
    }

    protected function pushToQueue($job, array $params)
    {
        $this->{$job}($params);
    }

    /**
     * TODO: Set up UPI under application Auth
     * The apache vhost configuration should forward
     * the mode in the authorization header
     *
     * (We'll have different IPs for prod and live)
     */
    protected function setMode($mode = Mode::TEST)
    {
        \Database\DefaultConnection::set($mode);
    }

    protected function RespListAccount(array $params)
    {
        $this->setMode();

        $mobile = $params['mobile'];

        $service = new CustomerService;

        $params['bank_accounts'] = $service->fetchBankAccountsByContact($mobile)['items'];

        $input = [];

        $input['params'] = $params;

        $input['method'] = 'RespListAccount';

        $this->callUpiGateway('makeRequest', $params);
    }

    /**
     * Verifies and sets the MPIN of the account
     * @param array $creds
     *  'last6'
     *  'expiry'
     *  'otp'
     *  'mpin'
     *  'account'
     *    'IFSC'
     *    'NUM'
     *  'reqMsgId'
     */
    protected function RespRegMob(array $creds)
    {
        $this->setMode();

        $success = (new CustomerService)->setMPINForBankAccounts($creds['account']['NUM'], $creds);

        // We need to respond to UPI with a success/failure response

        $result = $success ? 'SUCCESS' : 'FAILURE';

        $input = [
            'method'    =>  'RespRegMob',
            'params'    =>  [
                'success'   =>  $result,
                'reqMsgId'  =>  $creds['reqMsgId']
            ]
        ];

        sd($this->callUpiGateway('makeRequest', $input));
    }
}
