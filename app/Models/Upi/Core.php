<?php

namespace RZP\Models\Upi;

use Cache;
use RZP\Models\Customer;
use RZP\Models\P2p;
use RZP\Models\Device;
use RZP\Models\Base;
use RZP\Models\Vpa;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const EXCLUDED_PSPS = 'upi:excluded_psps';

    protected $customerService;

    /**
     * TODO: Set up UPI under application Auth
     * The apache vhost configuration should forward
     * the mode in the authorization header
     *
     * (We'll have different IPs for prod and live)
     */
    public function __construct()
    {
        parent::__construct();

        $this->customerService = new Customer\Service;
    }

    public function callUpiGateway($method, array $gatewayData = [])
    {
        // TODO: Take this as a parameter later when required.
        $gateway = 'upi_npci';

        try
        {
            $response = $this->app['gateway']->call($gateway, $method, $gatewayData, $this->mode);

            return ['success' => true, 'txnId' => $response['txn_id'], 'msgId' => $response['msg_id']];
        }
        catch (\Exception $ex)
        {
            return ['success' => false, 'msg' => $ex->getMessage()];

            // Throw exception instead of returning success as true/false
            // throw $ex;
        }
    }

    /**
     * Handles the callback received by UPI for a request sent by us.
     *
     * @param $api
     * @param $id
     * @param $body
     *
     * @return mixed
     */
    public function handleUPIRequest($api, $id, $body)
    {
        $this->trace->info(
            TraceCode::GATEWAY_UPI_REQUEST_CALLBACK,
            [
                'api'  => $api,
                'id'   => $id,
                'body' => $body
            ]);

        $parsedRequest = $this->app['upi.client']->parse($body, $api);

        $requestData = [
            'api'            => $api,
            'id'             => $id,
            'body'           => $body,
            'parsed_request' => $parsedRequest,
        ];

        $response = $this->app['gateway']->call('upi_npci', 'handle_request', $requestData, $this->mode);

        $this->trace->info(
            'PAYMENT_TOPUP_REQUEST',
            $response);

        $this->cacheResponse($response);

        if ($response['queue'])
        {
            $this->pushToQueue($response['job'], $response['params']);
        }

        $ackXML = $this->app['gateway']->call('upi_npci', 'generateAckResponse', $requestData, $this->mode);

        return $ackXML;
    }

    protected function cacheResponse($response)
    {
        if (isset($response['params']) === false)
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
        if ($job)
        {
            $data = $this->{$job}($params);

            if (isset($params['txnId']))
            {
                $txnId = $params['txnId'];
                $key = "UPI.$txnId.response";
                Cache::forever($key, $data);
            }
        }
    }

    protected function RespListAccount(array $params)
    {
        $this->setModeAndDefaultConnection();

        $mobile = $params['mobile'];

        $params['bank_accounts'] = $this->customerService->fetchBankAccountsByContact($mobile)['items'];

        $input = [];

        $input['params'] = $params;

        $input['method'] = 'RespListAccount';

        $this->callUpiGateway('makeRequest', $params);
    }

    protected function getSharedAccount()
    {
        return $this->repo->merchant->getSharedAccount();
    }

    /**
     * Verifies and sets the MPIN of the account
     *
     * @param array $creds
     *    'last6'
     *    'expiry'
     *    'otp'
     *    'mpin'
     *    'account'
     *    'IFSC'
     *    'NUM'
     *    'reqMsgId'
     *
     * @return array
     */
    protected function RespRegMob(array $creds)
    {
        $this->setModeAndDefaultConnection();

        list($success, $error) = $this->customerService->setMPINForBankAccounts($creds['account']['NUM'], $creds);

        // We need to respond to UPI with a success/failure response

        $result = $success ? 'SUCCESS' : 'FAILURE';

        $input = [
            'method'    =>  'RespRegMob',
            'params'    =>  [
                'success'   =>  $result,
                'reqMsgId'  =>  $creds['reqMsgId']
            ]
        ];

        $this->callUpiGateway('makeRequest', $input);

        return [
            'success' => $success,
            'error' => $error
        ];
    }

    protected function RespSetCre(array $creds)
    {
        $this->setModeAndDefaultConnection();

        list($success, $error) = $this->customerService->setMPINForBankAccounts($creds['account']['NUM'], $creds);

        // We need to respond to UPI with a success/failure response

        $result = $success ? 'SUCCESS' : 'FAILURE';

        $input = [
            'method'    =>  'RespSetCre',
            'params'    =>  [
                'success'   =>  $result,
                'reqMsgId'  =>  $creds['reqMsgId']
            ]
        ];

        // $this->callUpiGateway('makeRequest', $input);

        return [
            'success'   => $success,
            'error'     => $error
        ];
    }

    protected function RespAuthDetails($arr)
    {
        $input = [
            'method'    =>  'RespAuthDetails',
            'params'    =>  $arr,
        ];

        $this->callUpiGateway('makeRequest', $input);
    }

    protected function AuthorizePayment($arr)
    {
        return (new P2p\Service)->completeAuthorization($arr['p2p_id'], $arr);
    }

    public function disallowVpaPsp(array $input)
    {
        $cache = $this->app['cache'];

        $excludedPsps = json_decode($cache->get(self::EXCLUDED_PSPS, '[]'), true);

        $newExcludedPsps = $input['psps'];

        $excludedPsps = array_unique(array_merge($excludedPsps, $newExcludedPsps));

        $cache->forever(self::EXCLUDED_PSPS, json_encode($excludedPsps));

        return ['excluded' => $excludedPsps, 'success' => true];
    }

    public function allowVpaPsp(array $input)
    {
        $cache = $this->app['cache'];

        $excludedPsps = json_decode($cache->get(self::EXCLUDED_PSPS, '[]'), true);

        $allowedPsps = $input['psps'];

        $excludedPsps = array_diff($excludedPsps, $allowedPsps);

        $cache->forever(self::EXCLUDED_PSPS, json_encode($excludedPsps));

        return ['excluded' => $excludedPsps, 'success' => true];
    }
}
