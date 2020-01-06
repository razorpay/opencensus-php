<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use Carbon\Carbon;
use Requests_Session;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Base\ProviderCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Terminal\Entity as TerminalEntity;


class Doppler
{
    const PAYMENT_AUTHORIZATION_SUCCESS_EVENT = 'success';

    const PAYMENT_AUTHORIZATION_FAILURE_EVENT = 'failure';

    const SESSION_ID = 'rzp_api_session';

    const CONTENT_TYPE_HEADER      = 'Content-Type';

    const ACCEPT_HEADER            = 'Accept';

    const APPLICATION_JSON         = 'application/json';

    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';

    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';

    const CONNECT_TIMEOUT = 1;

    const REQUEST_TIMEOUT = 5;

    const MAX_RETRY_COUNT = 1;

    const ERROR = 'error';

    const ERROR_MESSAGE = 'error_message';

    const ERROR_CODE = 'error_code';

    protected $app;

    protected $mode;

    protected $sns;

    protected $trace;

    protected $config;

    protected $request;

    protected $sns_topic;

    public function __construct($app, $dopplerTopic)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->sns = $app['sns'];

        $this->mode = $this->app['rzp.mode'];

        $this->sns_topic = $dopplerTopic;

        $this->config = $app['config']->get('applications.doppler');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->getUrl();

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    // sends event to doppler's topic
    public function sendFeedback(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, $internalErrorCode = null)
    {
        // We do not want to publish events in case for test mode payments
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        // publishing event to doppler's topic if payment method is card/upi/netbanking
        if (($payment->getMethod() === Method::CARD) or
            ($payment->getMethod() === Method::UPI)  or
            ($payment->getMethod() === Method::NETBANKING))
        {

            $eventData = $this->prepareEventForDoppler($payment, $authorizeStatus, $errorCode, $internalErrorCode);

            $this->sendDopplerEventRequest($eventData);
        }
    }

    /**
     * Dispatch event data to doppler service via SNS.
     *
     * @param array $eventData
     */
    protected function sendDopplerEventRequest(array $eventData)
    {
        try
        {
            $this->sns->publish(json_encode($eventData), $this->sns_topic);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED, $eventData);
        }
    }

    protected  function prepareEventForDoppler(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, $internalErrorCode = null)
    {

        $card = [];

        $upi = [];

        $netbanking = [];

        $terminalType = null;

        $gateway = null;

        $device = null;

        $browser = null;

        $os = null;

        // associated terminal from payment entity
        $terminal = $payment->terminal;

        if($terminal != null)
        {
            $gateway = $terminal->getGateway();

            $terminalType = $terminal->isShared() ? TerminalEntity::SHARED : TerminalEntity::DIRECT;
        }

        $paymentAnalytics = $payment->getMetadata('payment_analytics');

        if (is_null($paymentAnalytics) === false)
        {
            $device = $paymentAnalytics->getDevice();

            $browser = $paymentAnalytics->getBrowser();

            $os = $paymentAnalytics->getOs();
        }

        if($payment->hasCard() === true)
        {
            $card['card_iin'] = $payment->card->getIin();
            $card['card_network'] = $payment->card->getNetwork();
            $card['card_type'] = $payment->card->getType();
            $card['card_issuer'] = $payment->card->getIssuer();
            $upi['vpa'] = null;
            $upi['vpa_handle'] = null;
            $upi['psp'] = null;
            $upi['bank'] = null;
            $upi['type'] = null;
            $netbanking['bank'] = null;
        }

        if($payment->isUPI() === true)
        {
            $vpaHandle = $payment->getVpaHandleFromVpa();
            if (strlen($vpaHandle) == 0)
            {
                $vpaHandle = null;
            }
            $card['card_iin'] = null;
            $card['card_network'] = null;
            $card['card_type'] = null;
            $card['card_issuer'] = null;
            $upi['vpa'] = $payment->getVpa();
            $upi['vpa_handle'] = $vpaHandle;
            $upi['psp'] = ProviderCode::getPsp($vpaHandle) ?? null;
            $upi['bank'] = $payment->getBankName() ?? null;
            $upi['type'] = $payment->getMetadata('flow');
            $netbanking['bank'] = null;
        }

        if($payment->isNetbanking() === true)
        {
            $card['card_iin'] = null;
            $card['card_network'] = null;
            $card['card_type'] = null;
            $card['card_issuer'] = null;
            $upi['vpa'] = null;
            $upi['vpa_handle'] = null;
            $upi['psp'] = null;
            $upi['bank'] = null;
            $upi['type'] = null;
            $netbanking['bank'] = $payment->getBankName();
        }

        $reqObj = [
            'payment_id'            => $payment->getId(),
            'method'                => $payment->getMethod(),
            'merchant_id'           => $payment->getMerchantId(),
            'authorized'            => $authorizeStatus,
            'card'                  => $card,
            'upi'                   => $upi,
            'netbanking'            => $netbanking,
            'terminal'              => $payment->getTerminalId(),
            'gateway'               => $gateway,
            'terminalType'          => $terminalType,
            'device'                => $device,
            'os'                    => $os,
            'browser'               => $browser,
            'created_at'            => $payment->getCreatedAt(),
            'authorized_at'         => Carbon::now()->getTimestamp(),
            'error_code'            => $errorCode ?? null,
            'internal_error_code'   => $internalErrorCode ?? null,
        ];


        $data = [
            'session_id' => self::SESSION_ID,
            'reqObj'     => $reqObj,
        ];

        return $data;
    }

    protected function getUrl(): string
    {
        return $this->config['url'];
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
            self::X_RAZORPAY_APP_HEADER    => 'api',
        ];

        return $headers;
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        return $options;
    }

    public function sendRequest(string $method, string $path, string $content)
    {
        $url = $this->getUrl() . $path;

        $data = $this->jsonToArray($content);

        $headers[self::X_RAZORPAY_TASKID_HEADER] = $this->app['request']->getTaskId();

        $authentication = [
            $this->config['key'],
            $this->config['secret'],
        ];

        $options = [
            "connect_timeout" => self::CONNECT_TIMEOUT,
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $authentication,
        ];

        $request = [
            'url'       => $url,
            'method'    => $method,
            'content'   => $data,
            'options'   => $options,
            'headers'   => $headers,
        ];

        $this->trace->info(TraceCode::DOPPLER_SERVICE_SUCCESS_RATE_REQUEST, $request);

        $response = $this->sendRawRequest($request);

        $parsedResponse = $this->processResponse($response);

        $this->trace->info(TraceCode::DOPPLER_SERVICE_SUCCESS_RATE_RESPONSE, $parsedResponse ?? []);

        return $parsedResponse;
    }

    protected function sendRawRequest($request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                switch($request['method']) {
                    case Requests::POST:
                    case Requests::PUT:
                        $response = $this->request->request(
                            $request['url'],
                            $request['headers'],
                            json_encode($request['content']),
                            $request['method']);
                        break;
                    default:
                        $response = $this->request->request(
                            $request['url'],
                            $request['headers'],
                            null,
                            $request['method']);
                }

                break;
            }
            catch(\Requests_Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::DOPPLER_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;

                    continue;
                }

                $this->throwServiceErrorException($e);
            }
        }

        return $response;
    }

    protected function processResponse($response)
    {
        $responseBody = $this->jsonToArray($response->body);

        if ( $response->status_code != 200 )
        {
            if ((isset($responseBody[self::ERROR]) === true) &&
                (isset($responseBody[self::ERROR][self::ERROR_CODE])  === true) &&
                ($responseBody[self::ERROR][self::ERROR_CODE] === ErrorCode::BAD_REQUEST_ERROR))
            {
                $this->trace->error(
                    TraceCode::DOPPLER_SERVICE_BAD_REQUEST_ERROR,
                    ['response' => $response]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_DOPPLER,
                    null,
                    [],
                    $responseBody[self::ERROR][self::ERROR_MESSAGE]
                );
            }

            $this->trace->error(
                TraceCode::DOPPLER_SERVICE_ERROR,
                ['response' => $response]);

            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR
            );
        }

        return $responseBody;
    }

    protected function jsonToArray($json)
    {
        if (empty($json) === true)
        {
            return [];
        }

        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::DOPPLER_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_DOPPLER_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_DOPPLER_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

}
