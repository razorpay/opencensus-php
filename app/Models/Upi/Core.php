<?php

namespace RZP\Models\Upi;

use Cache;
use Database;
use RZP\Constants\Mode;
use RZP\Models\Customer;
use RZP\Models\P2p;
use RZP\Models\Device;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Core extends Base\Core
{
    protected $customerService;

    public function __construct()
    {
        parent::__construct();

        $this->customerService = new Customer\Service;

        $this->vpaService = new Vpa\Service;
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
                'api'   => $api,
                'id'    => $id,
                'body'  => $body
            ]);

        $parsedRequest = $this->app['upi.client']->parse($body, $api);

        $requestData = [
            'api'               => $api,
            'id'                => $id,
            'body'              => $body,
            'parsed_request'    => $parsedRequest,
        ];

        // TODO: Get the mode properly
        $response = $this->app['gateway']->call('upi_npci', 'handle_request', $requestData, 'test');

        $this->trace->info(
            'PAYMENT_TOPUP_REQUEST',
            $response);

        $this->cacheResponse($response);

        if ($response['queue'])
        {
            $this->pushToQueue($response['job'], $response['params']);
        }

        $ackXML = $this->app['gateway']->call('upi_npci', 'generateAckResponse', $requestData, 'test');

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
            $this->{$job}($params);
        }
    }

    /**
     * TODO: Set up UPI under application Auth
     * The apache vhost configuration should forward
     * the mode in the authorization header
     *
     * (We'll have different IPs for prod and live)
     *
     * @param string $mode
     */
    protected function setMode($mode = Mode::TEST)
    {
        Database\DefaultConnection::set($mode);
    }

    protected function RespListAccount(array $params)
    {
        $this->setMode();

        $mobile = $params['mobile'];

        $params['bank_accounts'] = $this->customerService->fetchBankAccountsByContact($mobile)['items'];

        $input = [];

        $input['params'] = $params;

        $input['method'] = 'RespListAccount';

        $this->callUpiGateway('makeRequest', $params);
    }

    public function deleteVpa($id)
    {
        return $this->vpaService->delete($id);
    }

    public function editVpa($id, $input)
    {
        return $this->vpaService->edit($id, $input);
    }

    public function getVpa($vpaId)
    {
        return $this->vpaService->getById($vpaId);
    }

    public function getVpas($input)
    {
        return $this->vpaService->getAll($input);
    }

    protected function getSharedAccount()
    {
        return $this->repo->merchant->getSharedAccount();
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

        $success = $this->customerService->setMPINForBankAccounts($creds['account']['NUM'], $creds);

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
}
