<?php

namespace RZP\Services;

use Config;
use Request;
use Throwable;
use \WpOrg\Requests\Exception as Requests_Exception;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Http\Request\Requests  as RzpRequest;

/**
 * Interface for api to talk to X as settlement_ondemand dummy merchant
 */
class RazorpayXClient
{
    const DEFAULT_ACCOUNT_TYPE = 'Other';

    const TYPE = 'customer';

    const REQUEST_TIMEOUT = 20;

    const ACCOUNT_TYPE = 'bank_account';

    const PURPOSE = 'payout';

    const POST = 'POST';

    const GET = 'GET';

    const ID = 'id';

    const STATUS = 'status';

    protected $app;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];
    }

    protected function getBasicAuthToken()
    {
        return ['username' => Config::get('applications.razorpayx_client.live.ondemand_x_merchant.username'),
                'secret'   => Config::get('applications.razorpayx_client.live.ondemand_x_merchant.secret')
        ];
    }

    public function createFundAccount($contactId, $data)
    {
        if ((empty($data['name']) === true) ||
            (empty($data['ifsc']) === true) ||
            (empty($data['account_number']) === true))
        {
            throw new Exception\InvalidArgumentException(
                'bank_account_name, bank_branch_ifsc and bank_account_number are mandatory');
        }

        $bankAccountDetails = [
                'name'           => $data['name'],
                'ifsc'           => $data['ifsc'],
                'account_number' => $data['account_number'],
        ];

        $data = [
            'contact_id'    => $contactId,
            'account_type'  => $data['account_type'],
            'bank_account'  => $bankAccountDetails,
        ];

        $response =  $this->makeRequest('fund_accounts', $data , self::POST);

        $responseMap = json_decode($response->body, true);

        if (isset($responseMap['id']) === false)
        {
            $data['bank_account']['account_number'] = 'xxx. length:' . strlen($data['bank_account']['account_number']);

            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_RAZORPAYX_FUND_ACCOUNT_CREATION_FAILURE,
                null,
                null,
                [
                    'response' => $responseMap,
                    'request'  => $data,
                ]);
        }

        return $responseMap;
    }

    public function createContact($data)
    {
        if (empty($data['name']) === true)
        {
            throw new Exception\InvalidArgumentException(
                'contact_name is mandatory');
        }

        $response = $this->makeRequest('contacts', $data , self::POST);

        $responseMap = json_decode($response->body, true);

        if (isset($responseMap['id']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_RAZORPAYX_CONTACT_CREATION_FAILURE,
                null,
                null,
                [
                    'response' => $responseMap,
                    'request'  => $data,
                ]);
        }

        return $responseMap;
    }

    public function makePayoutRequest($data, $idempotencyKey, bool $isMerchantWithXSettlementAccount)
    {
        if ((empty($data['fund_account_id']) === true) ||
            (empty($data['amount']) === true) ||
            (empty($data['currency']) === true) ||
            (empty($data['mode']) === true) ||
            (empty($data['reference_id']) === true))
        {
            throw new Exception\InvalidArgumentException(
                'fund_account_id, amount, currency, mode and reference_id are mandatory');
        }

        $this->trace->info(TraceCode::CREATE_SETTLEMENT_ONDEMAND_PAYOUT_REQUEST, [
                'settlement_ondemand_payout_id' => $data['reference_id'],
                'amount'                        => $data['amount'],
            ]);

        $data = $data + ['account_number'=> Config::get('applications.razorpayx_client.live.ondemand_x_merchant.account_number'),
                         'purpose'       => self::PURPOSE
                        ];

        $customHeaders = [
            'X-Payout-Idempotency' => $idempotencyKey,
        ];

        $responseBody = $this->makeRequest('payouts', $data , self::POST, $customHeaders)->body;

        $responseMap = json_decode($responseBody, true);

        $this->trace->info(TraceCode::CREATE_SETTLEMENT_ONDEMAND_PAYOUT_RESPONSE, [
            'settlement_ondemand_payout_id'    => $data['reference_id'],
            'response'                         => $responseMap,
        ]);

        if (isset($responseMap['id']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_CREATION_FAILURE,
                null,
                null,
                ['response' => $responseMap]);
        }

        if ($isMerchantWithXSettlementAccount and isset($responseMap['status']) === true and $responseMap['status'] === 'reversed')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_REVERSAL,
                null,
                null,
                ['response' => $responseMap]);
        }

        return $responseMap;
    }

    public function makeRequest($path, $data, $method, $customHeaders = [])
    {
        $settlementOndemandMerchantAuth = $this->getBasicAuthToken();

        $url = Config::get('applications.razorpayx_client.live.razorpayx_url') . $path;

        $headers = [
            'Content-Type'      => 'application/json',
        ];

        $headers = array_merge($customHeaders, $headers);

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$settlementOndemandMerchantAuth['username'], $settlementOndemandMerchantAuth['secret']],
        ];

        try
        {
            $response = RzpRequest::request(
                $url,
                $headers,
                empty($data)? '{}': json_encode($data),
                $method,
                $options);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_RAZORPAYX_FAILURE,
                '',
                $e->getMessage());
        }

        $this->trace->info(TraceCode::RAZORPAYX_CLIENT_RESPONSE, [
            'body'          => $response->body,
            'status_code'   => $response->status_code,
        ]);

        if (in_array($response->status_code, [503, 504], true) === true)
        {
            throw new Exception\GatewayTimeoutException('Response status: '. $response->status_code);
        }
        else if ($response->status_code >= 400)
        {
            $e = new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR);

            $data = ['status_code' => $response->status_code, 'body' => $response->body];
            $e->setData($data);

            throw $e;
        }

        return $response;
    }
}
