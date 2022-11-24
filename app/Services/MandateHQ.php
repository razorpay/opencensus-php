<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class MandateHQ
{
    const REQUEST_TIMEOUT = 60;

    const MANDATE_HQ_URLS = [
        'register_mandate'              => 'v1/mandates/register',
        'create_pre_debit_notification' => 'v1/mandates/%s/notifications',
        'report_payment'                => 'v1/mandates/%s/payments',
        'check_bin'                     => 'v1/iins/%s',
        'cancel_mandate'                => 'v1/mandates/%s/cancel',
        'validate_payment'              => 'v1/mandates/%s/payments/validate',
        'update_card_token'             => 'v1/mandates/%s/update_token'
    ];

    const VALID_400_ERROR_DESCRIPTIONS = [
        // mandate registration
        'invalid card number'                                             => ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED,
        'card not supported'                                              => ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED,
        'Issuing bank is not available for recurring'                     => ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED,

        // pre debit notification
        'Pre-debit notification daily limit exceeds'                      => ErrorCode::BAD_REQUEST_CARD_MANDATE_PRE_DEBIT_NOTIFICATION_MAXIMUM_LIMIT_REACHED,
        'Debit date out of range'                                         => ErrorCode::BAD_REQUEST_CARD_MANDATE_DEBIT_DATE_OUT_OF_RANGE,
        'Maximum allowed debits in current cycle exceeded'                => ErrorCode::BAD_REQUEST_CARD_MANDATE_MAXIMUM_ALLOWED_DEBIT_EXCEEDED_IN_CURRENT_CYCLE,

        // payment
        'Minimum time gap between notification and payment not honoured'  => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION,
        'Payment done before 24 hours from notification delivery time'    => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION,
        'predebit notification has not sent before 24 hours'              => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION,
        'Promised debit date not honoured'                                => ErrorCode::BAD_REQUEST_CARD_MANDATE_PROMISED_DEBIT_DATE_NOT_HONOURED,
        'payment already done for the cycle'                              => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_DEBIT_NOT_AS_PER_FREQUENCY,
        'captured at is not within mandate cycle'                         => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_DEBIT_NOT_AS_PER_FREQUENCY,
        'Mandate not in appropriate state'                                => ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE,

        // validate
        '24 hours have not elapsed since pre debit notification delivery' => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION,
        'notification AFA approval is rejected'                           => ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED,
        'notification AFA approval is expired'                            => ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED,
        'notification AFA approval is pending'                            => ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED,
        'customer opted out of the payment'                               => ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_OPTED_OUT_OF_PAYMENT,
        'mandate debit not as per frequency'                              => ErrorCode::BAD_REQUEST_CARD_MANDATE_PAYMENT_DEBIT_NOT_AS_PER_FREQUENCY,
        'Notification not in appropriate state'                           => ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_NOTIFIED,

        // common
        'Mandate not active'                                              => ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE,
        'mandate has been paused by user'                                 => ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE,
        'mandate has been cancelled by user'                              => ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE,
        'Mandate not in appropriate state to perform action'              => ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE,
    ];

    protected $app;

    protected $baseUrl;

    protected $testModeBaseUrl;

    protected $config;

    protected $key;

    protected $secret;

    protected $testModeKey;

    protected $testModeSecret;

    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $app['config']->get('applications.mandate_hq');

        $this->baseUrl = $this->config['url'];
        $this->key     = $this->config['username'];
        $this->secret  = $this->config['password'];

        $this->testModeBaseUrl = $this->config['test_mode_url'];
        $this->testModeKey     = $this->config['test_mode_username'];
        $this->testModeSecret  = $this->config['test_mode_password'];
        $this->trace = $app['trace'];
    }

    public function shouldSkipSummaryPage(): bool
    {
        return false;
    }

    /**
     * @param $bin
     *
     * @return bool
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    public function isBinSupported($bin): bool
    {
        $header = [];

        $merchant = $this->app['basicauth']->getMerchant();

        if($merchant != null)
        {
            $header['x-mandate-merchant-id'] = $merchant->getId();
        }

        $url = sprintf(self::MANDATE_HQ_URLS['check_bin'], $bin);

        $response = $this->sendRequest($url, 'post', [], $header);

        return $response['recurring_enabled'];
    }

    public function validatePayment($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['validate_payment'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function registerMandate($input)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $header = [];

        if($merchant != null)
        {
            $header['x-mandate-merchant-id'] = $merchant->getId();
        }

        return $this->sendRequest(self::MANDATE_HQ_URLS['register_mandate'], 'post', $input, $header);
    }

    public function createPreDebitNotification($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['create_pre_debit_notification'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function reportPayment($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['report_payment'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function cancelMandate($mandateId)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['cancel_mandate'], $mandateId);

        return $this->sendRequest($url, 'put', []);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $inputData
     * @param array  $headers
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    public function sendRequest($url, $method, array $inputData = [],$headers = [])
    {
        $baseUrl = $this->baseUrl;
        $key = $this->key;
        $secret = $this->secret;

        if ($this->app['rzp.mode'] === Mode::TEST) {
            $baseUrl = $this->testModeBaseUrl;
            $key = $this->testModeKey;
            $secret = $this->testModeSecret;
        }

        $url = $baseUrl . $url;

        $data = '';

        if (empty($inputData) === false)
        {
            $data = json_encode($inputData);
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$key, $secret],
        );

        $request = array(
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendMandateHQRequest($request);

        switch (true)
        {
            case stristr($url, 'register'):
                $requesturl = "mandate_register";
                break;
            case stristr($url, 'notifications'):
                $requesturl = "create_pre_debit_notification";
                break;
            case stristr($url, 'payments'):
                $requesturl = "report_payment";
                break;
            case stristr($url, 'cancel'):
                $requesturl = "cancel_mandate";
                break;
            case stristr($url, 'validate'):
                $requesturl = "validate_payment";
                break;
            case stristr($url, 'update_token'):
                $requesturl = "update_card_token";
                break;
            case stristr($url, 'iins'):
                $requesturl = "check_bin";
                break;
            default :
                $requesturl = $url;
                break;
        }
        $dimensions = [
            'status' => $response->status_code,
            'request' => $requesturl
        ];
        $this->trace->count(TraceCode::MANDATEHQ_REQUEST_COUNT, $dimensions);

        if ($response->status_code !== 200)
        {
            if (($response->status_code === 400) and
                (empty($response->body) === false))
            {
                $data = json_decode($response->body, true);
                if (isset(self::VALID_400_ERROR_DESCRIPTIONS[$data['error']['description'] ?? '']))
                {
                    throw new Exception\BadRequestException(
                        self::VALID_400_ERROR_DESCRIPTIONS[$data['error']['description'] ?? ''],
                        null,
                        [
                            'response_body'        => $response->body ?? null,
                            'response_status_code' => $response->status_code,
                            'method'               => 'card',
                        ]
                    );
                }
            }

            throw new Exception\ServerErrorException(
                'Mandate HQ error',
                ErrorCode::SERVER_ERROR_MANDATE_HQ_REQUEST_FAILED,
                [
                    'response_body'        => $response->body ?? null,
                    'response_status_code' => $response->status_code,
                ]
            );
    }

        $decodedResponse = json_decode($response->body, true);

        $decodedResponse = $decodedResponse ?? [];

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        return $decodedResponse;
    }

    /**
     * @param $request
     *
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function sendMandateHQRequest($request)
    {
        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            throw new Exception\ServerErrorException(
                'Mandate HQ error',
                ErrorCode::SERVER_ERROR_MANDATE_HQ_REQUEST_FAILED,
                [],
                $e
            );
        }

        return $response;
    }

    public function updateTokenisedCardTokenInMandate($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['update_card_token'], $mandateId);

        return $this->sendRequest($url, 'patch', $input);
    }
}
