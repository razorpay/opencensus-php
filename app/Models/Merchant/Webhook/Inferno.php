<?php

namespace RZP\Models\Merchant\Webhook;

use App;
use Mail;

use RZP\Models\Feature;
use RZP\Http\Response\Header;
use RZP\Http\Response\StatusCode;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;
use RZP\Trace\TraceCode;

use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Exception\ServerErrorException;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Http\Client\Exception\TransferException;

class Inferno
{
    protected $job;

    protected $trace;

    protected $repo;

    protected $mode;

    protected $errorMessage;

    protected $event;

    protected $eventName;

    /**
     * Unix timestamp in milliseconds to capture when even payload was queued.
     * It is used to capture latency between queuing to actual firing.
     * Laravel's queue layer doesn't provide abstract method to get this value
     * although it is available in underlying queue systems e.g. sqs etc.
     *
     * @var int
     */
    protected $eventQueuedAt;

    /**
     * @see getEventContainedIds() method.
     * @var null|array
     */
    protected $eventContainedIds;

    protected $client = null;

    const HASH_ALGO = 'sha256';

    const WEBHOOK_FAILURE_HOURS = 24;

    const WEBHOOK_MAXIMUM_ATTEMPTS = 24;

    /**
     * We keep it internally as 10 seconds
     * but publicly we say it's only 5 seconds.
     */
    const WEBHOOK_TIMEOUT = 10;

    const WEBHOOK_REDIRECTS = 3;

    public function __construct()
    {
        $this->repo = new Repository;
    }

    /**
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        $app = App::getFacadeRoot();

        // Initialising trace here, as inferno is bound as singleton
        // to app container and we want fresh instance of trace to log
        // request metadata
        $this->trace = $app['trace'];

        $this->job = $job;

        $this->mode = $data['mode'];

        $this->event = $data['event'];
        $this->eventContainedIds = $this->getEventContainedIds();

        // TODO: Remove backward compatible code in few days having guaranteed
        // no old formatted job payload exists in queue.
        $this->eventName = $data['event_name'] ?? null;
        $this->eventQueuedAt = $data['queued_at'] ?? null;

        $this->trace->count(Metric::WEBHOOK_EVENTS_CONSUMED_TOTAL, ['event' => $this->eventName]);

        $webhook = $this->getActiveWebhook($data);

        if ($webhook === null)
        {
            $this->job->delete();

            return;
        }

        $request = $this->getRequestArray($data['event'], $webhook);

        $clientError = $this->sendRequest($request, $webhook);

        $this->updateWebhookPostFiring($clientError, $webhook);
    }

    protected function updateWebhookPostFiring($clientError, $webhook)
    {
        if ($clientError === false)
        {
            $this->webhookSuccessfullyFired($webhook);
        }
        else
        {
            $this->webhookFailure($webhook);
        }
    }


    public function sendEmail($webhook, $type)
    {
        $merchant = $webhook->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $options = [
            'mode'         => $this->mode,
            'type'         => $type,
            'event'        => $this->event,
            'errorMessage' => $this->errorMessage
        ];

        $merchant = $merchant->toArrayPublic();

        $webhook = $webhook->toArrayPublic();

        $webhookMail = new WebhookMail($webhook, $merchant, $options);

        Mail::send($webhookMail);
    }

    public function getRequestHeaders($hmac)
    {
        $headers = array(
            'User-Agent'    => 'Razorpay-Webhook/v1',
            'Content-Type'  => 'application/json',
            'Expect'        => null,
        );

        if (empty($hmac) === false)
        {
            $headers[Header::X_RAZORPAY_SIGNATURE] = $hmac;
        }

        return $headers;
    }

    public static function generateHMAC($payload, $secret)
    {
        // HMAC doesn't throw up an exception for NULL values.
        if (($secret === null) or ($payload === null))
        {
            return null;
        }

        $hmac = hash_hmac(self::HASH_ALGO, $payload, $secret);

        return $hmac;
    }

    public function makeRequest($request)
    {
        $factory = app()->make('httplug.message_factory.default');

        $request = $factory->createRequest('POST', $request['url'], $request['headers'], $request['content']);

        $httpClient = $this->createHttpClient();

        $response = $httpClient->sendRequest($request);

        return $response;
    }

    protected function createHttpClient()
    {
        // Plugin to get error-exceptions from responses of httpClient
        $errorPlugin = new ErrorPlugin();

        // PluginClient is the decorator around the httpClient that manages plugins
        // HttpClientDiscovery finds a suitable installed client that -
        // extends HttpClient (in this case Guzzle6 client)
        $pluginClient = new PluginClient(
            $this->getClient(),
            [$errorPlugin]
        );

        return $pluginClient;
    }

    /**
     * Set client is used for setting client in
     * test cases
     */
    public function setClient($client = null)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        if ($this->client === null)
        {
            $this->client = app()->make('httplug.default');
        }

        return $this->client;
    }

    protected function validatePublicIpAddress(array $request, Entity $webhook)
    {
        $url = $request['url'];

        if ($webhook->getValidator()->validatePublicIpAddress($url) === false)
        {
            $this->errorMessage = 'Webhook must point to a public IP address';

            return false;
        }
    }

    /**
     * This Function Will send request to Webhook Url, and get the response
     * In case of non-successful response it will return false,
     * Webhoook Handling i.e diasbling need to be handled after that.
     *
     * This function also sets class variable errorMessage, which is used while sending mails
     *
     * @param array  $request Options in array format for making request
     * @param Entity $webhook Webhook Entity
     *
     * @return bool Error
     * @throws \Throwable
     */
    public function sendRequest(array $request, Entity $webhook)
    {
        $response = null;

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            [
                'webhook_id'    => $webhook->getId(),
                'event_name'    => $this->eventName,
                'merchant_id'   => $webhook->merchant->getId(),
                'request'       => $request,
                'attempt'       => $this->job->attempts(),
                'contained_ids' => $this->eventContainedIds,
            ]);

        $this->pushQueuedToFiredLatencyMetrics();

        $clientError = $this->validateWebhookRequest($request, $webhook);

        if ($clientError === true)
        {
            return true;
        }

        $requestStartTime = millitime();

        try
        {
            $response = $this->makeRequest($request);
        }
        catch (\Throwable $e)
        {
            $clientError = true;

            $this->trace->count(
                Metric::WEBHOOK_REQUEST_FAILURES_TOTAL,
                [
                    'exception'   => str_replace('\\', '_', get_class($e)),
                    'status_code' => optional($response)->getStatusCode(),
                ]);

            switch(true)
            {
                case ($e instanceof ClientErrorException):
                    $response = $e->getResponse();
                    $msgPrefix = 'Client error: ';
                    break;

                case ($e instanceof ServerErrorException):
                    $response = $e->getResponse();
                    $msgPrefix = 'Server error: ';
                    break;

                case ($e instanceof HttpException):
                    $response = $e->getResponse();
                    $msgPrefix = 'Some error occurred: ';
                    break;

                case ($e instanceof NetworkException):
                    $msgPrefix = 'No response received due to network issues: ' . $e->getMessage();
                    break;

                case ($e instanceof RequestException):
                    $msgPrefix = 'The request is invalid: ' . $e->getMessage();
                    break;

                case ($e instanceof TransferException):
                    $msgPrefix = 'Something unexpected happened: ' . $e->getMessage();
                    break;

                default:
                    // We got an unexpected Error,
                    // Log the response but do not disable the Webhook
                    // Re-throw the exception

                    $this->traceWebhookResponse($webhook, $e->getMessage());

                    throw $e;
            }

            $this->traceWebhookResponse($webhook, $msgPrefix, $response);

            return $clientError;
        }

        $statusCode = $response->getStatusCode();

        $isSuccessStatusCode = $this->isSuccesssfulStatusCode($statusCode);

        $requestDuration = millitime() - $requestStartTime;

        if ($isSuccessStatusCode === true)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRED,
                [
                    'webhook_id'        => $webhook->getId(),
                    'event_name'        => $this->eventName,
                    'merchant_id'       => $webhook->merchant->getId(),
                    'response_code'     => $statusCode,
                    'response_headers'  => $response->getHeaders(),
                    'response_time'     => $requestDuration,
                    'contained_ids'     => $this->eventContainedIds,
                ]);

            $clientError = false;
        }
        else
        {
            $msgPrefix = '';

            $this->traceWebhookResponse($webhook, $msgPrefix, $response);

            $clientError = true;
        }

        $this->pushRequestAttemptMetrics($statusCode, $requestDuration);

        return $clientError;
    }

    protected function isSuccesssfulStatusCode($statusCode)
    {
        return (($statusCode >= StatusCode::SUCCESS) and
                ($statusCode < StatusCode::REDIRECTION));
    }

    protected function pushRequestAttemptMetrics(int $statusCode, int $requestDuration)
    {
        $dimensions = [
            'status_code'            => $statusCode,
            'event'                  => $this->eventName,
            'is_successs_tatus_code' => $this->isSuccesssfulStatusCode($statusCode),
        ];

        $this->trace->count(Metric::WEBHOOK_REQUEST_COMPLETED_TOTAL, $dimensions);
        $this->trace->histogram(Metric::WEBHOOK_REQUEST_DURATION_MILLISECONDS, $requestDuration, $dimensions);
    }

    protected function pushQueuedToFiredLatencyMetrics()
    {
        $attempts = $this->job->attempts();
        // For reasons job could be attempted multiple times. For usability and
        // metric's layer supporting it we limit string value used for attempts
        // in dimension. It would be "1", "2", "3" & ">3".
        $attemptsDimensionValue = $attempts < 4 ? strval($attempts) : ">3";

        // TODO: Remove condition for backward compatibility.
        if ($this->eventQueuedAt !== null)
        {
            $this->trace->histogram(
                Metric::WEBHOOK_QUEUED_TO_FIRED_MILLISECONDS,
                millitime() - $this->eventQueuedAt,
                [
                    'attempts' => $attemptsDimensionValue,
                ]);
        }
    }

    /**
     * This Will Trace the Webhook Data for Various Exception Response
     * Depending on exception thrown, sometime we have getResponse(),
     * if available, then use it for logging and creating ErrorMessage which is used to send mail
     *
     * @param Entity     $webhook   Webhook Entity
     * @param string     $msgPrefix Message Prefix which will be appended before $response Failure reason if any
     * @param array|null $response  Response if any
     */
    protected function traceWebhookResponse(Entity $webhook, string $msgPrefix = '', $response = null)
    {
        $webhookData = [
            'webhook_id'        => $webhook->getId(),
            'merchant_id'       => $webhook->merchant->getId(),
            'contained_ids'     => $this->eventContainedIds,
        ];

        $responseData = $this->getResponseData($msgPrefix, $response);

        $this->trace->info(
            TraceCode::WEBHOOK_RESPONSE_FAILURE,
            $webhookData + $responseData);
    }

    // This function also sets class variable errorMessage, which is used while sending mails
    protected function getResponseData(string $msgPrefix = '', $response = null)
    {
        $this->errorMessage = $msgPrefix;

        $data = [
            'status_code' => '',
            'headers'     => [],
        ];

         if ($response !== null)
         {
             $data = [
                 'status_code' => $response->getStatusCode(),
                 'headers'     => $response->getHeaders(),
             ];

             $this->errorMessage .= $response->getReasonPhrase();
         }

         $data['exception'] = $this->errorMessage;

         return $data;
    }

    protected function getRequestArray($event, $webhook)
    {
        $secret = $webhook->getSecret();

        $hmac = static::generateHMAC($event, $secret);

        $headers = $this->getRequestHeaders($hmac);

        $request = [
            'url'       => $webhook->getUrl(),
            'method'    => 'post',
            'content'   => $event,
            'headers'   => $headers
        ];

        $request['options'] = [
            'timeout'   => self::WEBHOOK_TIMEOUT,
            'redirects' => self::WEBHOOK_REDIRECTS,
        ];

        return $request;
    }

    /**
     * Validate the request before triggering
     *
     * @param array  $request
     * @param Entity $webhook
     *
     * @return bool
     */
    protected function validateWebhookRequest(array $request, Entity $webhook): bool
    {
        $clientError = false;
        $failureType = null;

        // Ensure that we are not hitting a private IP address
        if ($this->validatePublicIpAddress($request, $webhook) === false)
        {
            $clientError = true;
            $failureType = 'public_ip';
        }

        // If an error occurred, push relevant metrics
        if ($clientError === true)
        {
            $this->trace->count(
                Metric::WEBHOOK_VALIDATION_FAILURES_TOTAL,
                [
                    'event'        => $this->eventName,
                    'failure_type' => $failureType,
                ]);
        }

        return $clientError;
    }

    protected function webhookSuccessfullyFired(Entity $webhook)
    {
        $webhook->setLastSuccessfulAt();

        $this->repo->saveOrFail($webhook);

        $this->job->delete();
    }

    /**
     * If the number of job attempts is greater than the max attempts,
     * we delete the job.
     * If the last successful webhook hit was more than 24 hours ago,
     * We deactivate the webhook. We send a deactivation email.
     * We do not send any failure email in this case.
     *
     * In every other case, we send a failure email.
     *
     * @param Entity $webhook
     */
    protected function webhookFailure(Entity $webhook)
    {
        $deleteJobFlag = false;

        if (($this->job->attempts() > self::WEBHOOK_MAXIMUM_ATTEMPTS))
        {
            $deleteJobFlag = true;
        }

        $lastSuccessDifference = $webhook->getTimeDifferenceFromLastSuccessInHour();

        $toDisableWebhook =
            (($lastSuccessDifference > self::WEBHOOK_FAILURE_HOURS) and
             ($webhook->disableOnFailure() === true));

        // If (LSA - current time) > 24hrs, and.
        // webhook disable_on_failure is set to true
        // we mark webhook deactivated.
        if ($toDisableWebhook === true)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_DEACTIVATE,
                [
                    'webhook_id'  => $webhook->getId(),
                    'merchant_id' => $webhook->merchant->getId(),
                ]
            );

            $this->trace->count(Metric::WEBHOOK_DEACTIVATED_TOTAL);

            $this->disableWebhook($webhook);

            $this->sendEmail($webhook, 'deactivate');

            $deleteJobFlag = true;
        }
        else
        {
            $this->sendEmail($webhook, 'failure');
        }

        $this->updateJob($deleteJobFlag);
    }

    protected function updateJob($deleteJobFlag = true)
    {
        if ($deleteJobFlag === true)
        {
            $this->job->delete();
        }
        else
        {
            // Attempt again after 1 hour
            $this->job->release(3600);
        }
    }

    protected function disableWebhook(Entity $webhook)
    {
        $webhook->deactivate();

        $this->repo->saveOrFail($webhook);
    }

    protected function getActiveWebhook($data)
    {
        $mode = $data['mode'];

        $webhook = $this->repo
                        ->connection($mode)
                        ->find($data['webhook_id']);

        if ($webhook === null)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRING,
                [
                    'data'       => $data,
                    'webhook_id' => $data['webhook_id'],
                ]);
        }
        else if ($webhook->isActive() === false)
        {
            $webhook = null;
        }

        return $webhook;
    }

    /**
     * Returns pairs of (entity-name, id) for the entities webhook event contains.
     * @return array
     */
    protected function getEventContainedIds(): array
    {
        if ($this->eventContainedIds === null)
        {
            $event = json_decode($this->event, true);
            foreach ($event['contains'] as $k)
            {
                $id = $event['payload'][$k]['entity']['id'] ?: null;
                if ($id !== null)
                {
                    $this->eventContainedIds[$k] = $id;
                }
            }
        }

        return $this->eventContainedIds;
    }
}
