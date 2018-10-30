<?php

namespace RZP\Services;

use Requests;
use Lib\PhoneBook;
use Requests_Response;
use Razorpay\Trace\Logger;

use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * MyOperator service is an interface for support related features. E.g.
 * Processing a support call request from merchant and arranging a call using
 * the external service -MyOperator.
 */
class MyOperator
{
    const API_BASE_URL                           = 'https://developers.myoperator.co';
    const API_CALL_OUTBOUND_PATH                 = '/call/outbound';

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

    public function __construct(Logger $trace, array $config)
    {
        $this->trace  = $trace;
        $this->config = $config;
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
        list ($code, $number) = $this->splitContactAndGetCodeAndNumber($input['contact']);

        $payload = [
            'country_code'   => $code,
            'contact_number' => $number,
        ];
        $resp = $this->makeCalLOutboundApiRequest($payload);

        return $this->validateResponse($resp);
    }

    /**
     * Splits contact number and gets normalized country code (string) and
     * contact number (integer) in favor of MyOperator API request.
     *
     * @param string $contact
     *
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws \libphonenumber\NumberParseException
     */
    protected function splitContactAndGetCodeAndNumber(string $contact): array
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

        return [$code, $number];
    }

    protected function makeCalLOutboundApiRequest(array $payload): Requests_Response
    {
        $endpoint = sprintf(
            '%s%s?token=%s',
            self::API_BASE_URL,
            self::API_CALL_OUTBOUND_PATH,
            $this->config['api_token']);

        $headers = [
            'Accept'       => 'application/json',
            'Content-type' => 'application/json',
        ];

        $options = [
            'timeout' => self::API_TIMEOUT,
        ];

        $this->trace->info(TraceCode::MYOPERATOR_CALL_OUTBOUND_API_REQ, compact('payload'));

        return Requests::post($endpoint, $headers, $payload, $options);
    }

    /**
     * Validates remote API response and returns array response.
     *
     * @param  Requests_Response $resp
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    protected function validateResponse(Requests_Response $resp): array
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
}
