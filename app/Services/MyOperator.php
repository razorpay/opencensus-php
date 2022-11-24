<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use Lib\PhoneBook;
use \WpOrg\Requests\Response;
use Razorpay\Trace\Logger;

use RZP\Http\BasicAuth\BasicAuth;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * MyOperator service is an interface for support related features. E.g.
 * Processing a support call request from merchant and arranging a call using
 * the external service -MyOperator.
 */
class MyOperator
{
    const API_BASE_URL                           = 'https://obd-api.myoperator.co';
    const API_CALL_OUTBOUND_PATH                 = '/obd-api-v1';

    // Remote API set timeout in seconds.
    const API_TIMEOUT                            = 5;

    // Counter metric which gets triggered per remote API call, has status = success|failure as labels.
    const MYOPERATOR_CALL_OUTBOUND_API_RES_TOTAL = 'myoperator_call_outbound_api_res_total';

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var BasicAuth
     */
    private $auth;

    public function __construct(Logger $trace, array $config, BasicAuth $auth)
    {
        $this->trace  = $trace;
        $this->config = $config;
        $this->auth   = $auth;
    }

    /**
     * Triggers /call/outbound call of MyOperator.
     *
     * @param  array $input (Contains string contact number)
     *
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws \libphonenumber\NumberParseException
     */
    public function submitSupportCallRequest(array $input): array
    {
        $payload = [
            'number'         => $this->splitContactAndGetCodeAndNumber($input['contact']),
            'type'           => "2",
        ];
        $resp = $this->makeCalLOutboundApiRequest($payload, self::API_CALL_OUTBOUND_PATH,Requests::POST);

        return $this->validateResponse($resp);
    }

    /**
     * Splits contact number and gets normalized country code (string) and
     * contact number (integer) in favor of MyOperator API request.
     *
     * @param string $contact
     *
     * @return string
     * @throws BadRequestValidationFailureException
     * @throws \libphonenumber\NumberParseException
     */
    protected function splitContactAndGetCodeAndNumber(string $contact): String
    {
        $phonebook = new PhoneBook($contact, true);

        if ($phonebook->isValidNumber() === false)
        {
            throw new BadRequestValidationFailureException("Invalid contact number - {$contact}");
        }

        $phoneNumber = $phonebook->getPhoneNumber();
        $code        = $phoneNumber->getCountryCode();
        $code        = (($code === null) or ($code === 91)) ? '+91' : (string) $code;
        $number      = $phoneNumber->getNationalNumber();

        return $code.$number;
    }

    protected function makeCalLOutboundApiRequest(array $payload, $path, $method)
    {
        $this->trace->info(TraceCode::MYOPERATOR_CALL_OUTBOUND_API_REQ, compact('payload'));
        $endpoint = self::API_BASE_URL . $path;
        $headers = [
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
            'x-api-key'    => $this->config['x_api_key'],
        ];
        $options = [
            'timeout' => self::API_TIMEOUT,
        ];

        if($method == Requests::POST)
        {
            //redacted secrets so that they do not appear in logs
            $payload += $this->getToken() +
                        ['company_id' => $this->config['x_company_id']] +
                        ["public_ivr_id"  => $this->config['x_public_ivr_id']];

            $payload = json_encode($payload);

            return Requests::post($endpoint, $headers, $payload, $options);
        }
        else
        {
            $endpoint = $endpoint."?token=".$this->config['api_token'];
            return Requests::get($endpoint, $headers, $options);
        }
    }

    /**
     * Validates remote API response and returns array response.
     *
     * @param  \WpOrg\Requests\Response $resp
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    protected function validateResponse($resp): array
    {
        $code      = $resp->status_code;
        $body      = $resp->body;
        $jsonResp  = json_decode($body, true);
        $jsonError = json_last_error();
        $success   = (($jsonError === JSON_ERROR_NONE) and ($jsonResp['status'] === 'success'));

        $this->trace->info(
            TraceCode::MYOPERATOR_CALL_OUTBOUND_API_RES,
            compact('code', 'body', 'success'));

        $this->trace->count(self::MYOPERATOR_CALL_OUTBOUND_API_RES_TOTAL, compact('success'));

        if ($success === false)
        {
            throw new BadRequestValidationFailureException(
                'Request failed, please try again later.',
                null,
                compact('code', 'body'));
        }

        return $jsonResp;
    }

    /**
     * Returns the array ["token" => "some_token"] based on the product(primary/banking)
     *
     * @return array
     */
    protected function getToken(): array
    {
        $isBanking = $this->auth->isProductBanking();
        $token = null;
        if($isBanking === true)
        {
            $token = $this->config['x_api_token'];
        } else
        {
            $token = $this->config['api_token'];
        }

        if($token === null)
        {
            $this->trace->error("No token found for MyOperator");
        }
        return ['secret_token' => $token];
    }

    public function getProxyCallToMyOperatorV1($path): array
    {
        $resp = $this->makeCalLOutboundApiRequest([], $path, Requests::GET);

        return $this->validateResponse($resp);
    }

    public function postProxyCallToMyOperatorV2($path, $input): array
    {
        $this->trace->info(TraceCode::MYOPERATOR_CALL_CAMPAIGN_API_REQ, ["call_id" => $input["reference_id"]]);

        $input['secret_token'] = $this->config['secret_token'];

        return  $this->makeApiRequestAndGetResponse($input, $path, Requests::POST);
    }

    protected function makeApiRequestAndGetResponse(array $payload, $path,  $method): array
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'x-api-key'     => $this->config['x_api_key'],
        ];
        $options = [
            'timeout' => self::API_TIMEOUT,
        ];

        $request = [
            'url' => self::API_BASE_URL . $path,
            'headers'   => $headers,
            'content'   => json_encode($payload),
            'options'   => $options,
            'method'    => $method,
            ];

        return $this->makeRequest($request);
    }

    protected function makeRequest($request)
    {
        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['options']
        );

        $this->trace->info(TraceCode::MYOPERATOR_CALL_CAMPAIGN_API_RES, ["response_status" => $response->status_code]);

        return json_decode($response->body, true);
    }
}
