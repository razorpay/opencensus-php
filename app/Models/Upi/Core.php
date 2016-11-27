<?php

namespace RZP\Models\Upi;

use RZP\Models\Customer\Service as CustomerService;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function callUpiGateway($method, array $gatewayData)
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
        $parsedRequest = $this->app['upi.client']->parse($body, $api);

        $params = compact('api', 'id', 'body', 'parsedRequest');

        $response = $this->app['gateway']->call('upi_npci', 'handleRequest', $params, 'test');

        if ($response['queue'])
        {
            $this->pushToQueue($response['job'], $response['params']);
        }

        $ackXML = $this->app['gateway']->call('upi_npci', 'generateAckResponse', $params, 'test');

        return $ackXML;
    }

    protected function pushToQueue($job, array $params)
    {
        $this->{$job}($params);
    }

    protected function RespListAccount(array $params)
    {
        /**
         * TODO: Set up UPI under application Auth
         * The apache vhost configuration should forward
         * the mode in the authorization header
         *
         * (We'll have different IPs for prod and live)
         */
        \Database\DefaultConnection::set('test');

        $mobile = $params['mobile'];

        $service = new CustomerService;

        $params['bank_accounts'] = $service->fetchBankAccountsByContact($mobile)['items'];

        $input = [];

        $input['params'] = $params;

        $input['method'] = 'RespListAccount';

        $this->callUpiGateway('makeRequest', $params);
    }
}
