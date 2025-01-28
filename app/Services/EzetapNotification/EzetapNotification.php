<?php

namespace RZP\Services\EzetapNotification;

use Throwable;
use Razorpay\Trace\Logger as Trace;
use \WpOrg\Requests\Exception as Requests_Exception;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Http\RequestHeader;
use RZP\Models\Event\Entity;
use RZP\Http\Request\Requests;


/**
 * Interface for api to talk to ezetap service
 */
class EzetapNotification
{
    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    const HEADERS = 'headers';
    const CONTENT = 'content';
    const OPTIONS = 'options';
    const STATUS_CODE = 'status_code';

    protected $app;

    protected $trace;

    protected $config;

    public static $omniEnabledEvent  = [
        'qr_code.created',
        'qr_code.credited',
        'qr_code.closed',
        'payment.failed',
        'payment.captured',
        'refund.created',
        'refund.processed',
        'refund.failed',
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    public function sendEzetapRequest(
        Entity $event,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT)
    {
        $startTimeMs = microtime(true) * 1000;

        $metric      = new EzetapNotificationMetric();
        $errorMessage = null;
        try
        {
            if ($this->isEzetapNotificationEvent($event) === false)
            {
                return null;
            }

            $input = $event->toArrayPublic();

            $url = $this->getUrl();

            $signature = $this->getSignatureDetails($input);

            $request = $this->getRequest($url, $signature, $input, $timeout, $connectTimeout);

            $this->trace->info(TraceCode::EZETAP_SERVICE_REQUEST, $request);

            $responseBody = $this->sendRawRequest($request);

            $this->trace->info(TraceCode::EZETAP_SERVICE_RESPONSE, [
                'responseBody' => $responseBody,
                'message'      => 'EZETAP_SERVICE_RESPONSE',
            ]);

            return $responseBody;
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();
        }
        finally
        {
            $metric->pushEzetapNotificationMetrics($input, $errorMessage);
        }

        $metric->pushEzetapNotificationLatencyMetrics($input, $startTimeMs);

        return $responseBody;
    }

    public function sendDeviceNotification(
        Entity $event,
        int $timeout = self::TIMEOUT,
        int $connectTimeout = self::CONNECT_TIMEOUT
    )
    {
        $metric = new EzetapNotificationMetric();
        $errorMessage = null;
        try
        {

            $input = $event->toArrayPublic();

            $url = $this->getDeviceWebhookUrl();

            $this->trace->info(TraceCode::EZETAP_DEVICE_NOTIFICATION_REQUEST_PAYLOAD, [
                'request_body' => $input,
                'url'          => $url,
                'message'      => 'EZETAP_DEVICE_NOTIFICATION_REQUEST_PAYLOAD',
            ]);

            $signature = $this->getSignatureDetails($input);

            $request = $this->getRequest($url, $signature, $input, $timeout, $connectTimeout);

            $responseBody = $this->sendRawRequest($request);

            $this->trace->info(TraceCode::EZETAP_DEVICE_NOTIFICATION_RESPONSE, [
                'responseBody' => $responseBody,
                'message'      => 'EZETAP_DEVICE_NOTIFICATION_RESPONSE',
            ]);

            return $responseBody;
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();
        }
        finally
        {
            $metric->pushEzetapNotificationMetrics($input, $errorMessage);
        }

    }


    protected function isEzetapNotificationEvent(Entity $event): bool
    {
        $isOmniMerchant      = $event->merchant->isOmniEnabled();
        $isEnabledEvent      = in_array($event->event, self::$omniEnabledEvent, true);
        $isExperimentEnabled = $this->checkIfEzetapNotificationSplitzExperimentEnabled($event->merchant->getMerchantId());

        $this->trace->info(TraceCode::EZETAP_NOTIFICATION_EVENT_CHECK, [
            '$isOmniMerchant'      => $isOmniMerchant,
            '$isEnabledEvent'      => $isEnabledEvent,
            '$isExperimentEnabled' => $isExperimentEnabled,
            'message'              => 'Notify ezetap event check',
        ]);

        if ($isOmniMerchant === true and
            $isEnabledEvent === true and
            $isExperimentEnabled === true)
        {
            return true;
        }

        return false;
    }

    public function checkIfEzetapNotificationSplitzExperimentEnabled($merchantId)
    {
        try
        {
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->config->get('app.enable_ezetap_notification_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response   = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'merchant_id'   => $merchantId,
                '$response'     => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if ($key === 'result' && $value === 'on')
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ENABLE_EZETAP_NOTIFICATION_SPLITZ_ERROR
            );
        }

        return false;
    }

    protected function getUrl(): string
    {
        $url = $this->config->get('applications.ezetap-notification.url');

        return $url;
    }

    protected function getDeviceWebhookUrl()
    {
        return $this->config->get('applications.ezetap-notification.device_webhook_url');
    }


    protected function getSignatureDetails(array $input)
    {
        $secret = $this->config->get('applications.ezetap-notification.secret');
        $hashSignature = hash_hmac(HashAlgo::SHA256, json_encode($input), $secret);
        return $hashSignature;
    }

    protected function getRequest(string $url, String $signature, array $input,
                                  int $timeout = self::TIMEOUT, int $connectTimeout = self::CONNECT_TIMEOUT): array
    {

        $request = [
            'url'     => $url,
            'method'  => Requests::POST,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
                RequestHeader::X_TASK_ID    => $this->app['request']->getTaskId() ?? null,
                'X-Razorpay-Signature'      => $signature,
            ],
            'content' => json_encode($input),
            'options' => [
                'timeout'         => $timeout,
                'connect_timeout' => $connectTimeout
            ]
        ];

        return $request;
    }


    /**
     * @param array $request
     *
     * @return string $response
     * @throws Throwable
     */
    protected function sendRawRequest(array $request)
    {
        try
        {
            $responseBody = $this->sendRequest($request)[self::CONTENT];

            return $responseBody;
        }
        catch (Requests_Exception $e)
        {
            $errorCode = TraceCode::EZETAP_SERVICE_REQUEST_FAILED;

            if (checkRequestTimeout($e) === true)
            {
                $errorCode = TraceCode::EZETAP_SERVICE_REQUEST_TIMEOUT;
            }

            $this->trace->traceException($e, Trace::ERROR, $errorCode);

            throw $e;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EZETAP_SERVICE_REQUEST_FAILED
            );

            throw $ex;
        }
    }

    protected function sendRequest(array $request)
    {
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
        }

        $headers = $request['headers'] ?? [];

        $method = $request['method'] ?? Requests::POST;

        $request['options']['timeout'] = $request['options']['timeout'] ?? static::TIMEOUT;

        $request['options']['connect_timeout'] = $request['options']['connect_timeout'] ?? static::CONNECT_TIMEOUT;

        $response = Requests::request(
            $request['url'],
            $headers,
            $request['content'],
            strtoupper($method),
            $request['options']);

        $this->validateResponse($response);

        return
            [
                self::HEADERS     => $response->headers->getAll(),
                self::CONTENT     => $response->body,
                self::STATUS_CODE => $response->status_code,
            ];
    }

    protected function validateResponse($response)
    {
        $statusCode = $response->status_code;

        if (in_array($statusCode, [503, 504], true) === true)
        {
            throw new Exception\IntegrationException(
                'Response status: ' . $statusCode,
                ErrorCode::SERVER_ERROR_EZETAP_SERVICE_TIMEOUT,
                [
                    'status_code' => $statusCode,
                    'body'        => $response->body,
                ]);
        }
        else
        {
            if ($statusCode >= 500)
            {
                throw new Exception\IntegrationException(
                    'Response status: ' . $statusCode,
                    ErrorCode::SERVER_ERROR_EZETAP_SERVICE_ERROR,
                    [
                        'status_code' => $statusCode,
                        'body'        => $response->body,
                    ]);
            }
            else
            {
                if ($statusCode >= 400)
                {
                    throw new Exception\IntegrationException(
                        'Response status: ' . $statusCode,
                        ErrorCode::SERVER_ERROR_EZETAP_INTEGRATION_ERROR,
                        [
                            'status_code' => $statusCode,
                            'body'        => $response->body,
                        ]);
                }
            }
        }
    }

}
