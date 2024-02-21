<?php

namespace RZP\Services;

use App;
use Request;
use Lib\PhoneBook;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;


class BillMe
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    const X_REQUEST_TASK_ID = 'X-Razorpay-TaskId';

    const DEFAULT_REQUEST_TIMEOUT   = 60;

    const RESPONSE_CODE     = 'code';

    const MODE = 'mode';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.bill_me');

        $this->baseUrl = $this->config['url'];

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : Mode::LIVE;

        $this->request = $app['request'];

        $this->secret = $this->config['bill_me_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function postPaymentDataToBillMe(array $input, bool $throwExceptionOnFailure = false)
    {
        $this->separateCountryCodeFromContact($input);

        $output = $this->sendRequest("bl/create", Requests::POST, $input, $throwExceptionOnFailure, 90);

        return $output['body'];
    }

    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false,
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT,
        bool $retry = false)
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        $response = $this->sendBillMeRequest($request, $endpoint);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::BILL_ME_RESPONSE,
            ["decoded_response" => $decodedResponse ?? [],
                "statusCode" => $response->status_code
            ]);

        return $decodedResponse;
    }

    protected function generateRequest(string $endpoint, string $method, array $data, int $timeout): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout'      => $timeout,
        ];

        $this->setHeaders();

        $headers = $this->headers;

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    protected function sendBillMeRequest(array $request, string $endpoint)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BILL_ME_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $response;
    }

    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]              = 'application/json';
        $headers[self::CONTENT_TYPE]        = 'application/json';
        $headers['accesskey']               = $this->secret;

        $this->headers = $headers;
    }

    private function separateCountryCodeFromContact(array &$input)
    {
        if ((isset($input['contact']) === true))
        {
            $phoneBook = new PhoneBook($input['contact'], false);

            $countryCode = '+' . $phoneBook->getPhoneNumber()->getCountryCode();

            $contact = $phoneBook->getRawInput();

            $contactWithoutCountryCode = Str::startsWith($contact, $countryCode) ? substr($contact, strlen($countryCode)) : null;

            $input['contact'] = $contactWithoutCountryCode;

            $input['country_code'] = $countryCode;
        }
    }

}
