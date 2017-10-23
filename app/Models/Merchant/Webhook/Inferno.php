<?php

namespace RZP\Models\Merchant\Webhook;

use App;
use Mail;
use Requests;

use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use RZP\Http\Response\Header;
use RZP\Http\Response\StatusCode;

use Http\Discovery\HttpClientDiscovery;
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

        // initialising trace here, as inferno is bound as singleton
        // to app container and we want fresh instance of trace to log
        // request metadata
        $this->trace = $app['trace'];

        $this->job = $job;

        $this->mode = $data['mode'];

        $this->event = $data['event'];

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
        $options = [
            'mode'         => $this->mode,
            'type'         => $type,
            'event'        => $this->event,
            'errorMessage' => $this->errorMessage
        ];

        $merchant = $webhook->merchant->toArrayPublic();

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
        // hmac doesn't throw up an exception for NULL values.
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
        $clientError = true;

        $response = null;

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            [
                'webhook_id'  => $webhook->getId(),
                'merchant_id' => $webhook->merchant->getId(),
                'request'     => $request,
                'attempt'     => $this->job->attempts(),
            ]);

        $timeOfRequest = microtime(true);

        try
        {
            $response = $this->makeRequest($request);
        }
        catch (\Throwable $e)
        {
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

        if ($this->isSuccesssfulStatusCode($statusCode) === true)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRED,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'response_code'     => $statusCode,
                    'response_headers'  => $response->getHeaders(),
                    'response_time'     => (microtime(true) - $timeOfRequest),
                ]);

            $clientError = false;
        }
        else
        {
            $msgPrefix = '';

            $this->traceWebhookResponse($webhook, $msgPrefix, $response);
        }

        return $clientError;
    }

    protected function isSuccesssfulStatusCode($statusCode)
    {
        return (($statusCode >= StatusCode::SUCCESS) and
                ($statusCode < StatusCode::REDIRECTION));
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
            'merchant_id'       => $webhook->merchant->getId()
        ];

        $responseData = $this->getResponseData($msgPrefix, $response);

        $this->trace->info(
            TraceCode::WEBHOOK_RESPONSE_FAILURE,
            $webhookData + $responseData);
    }

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
            'url' => $webhook->getUrl(),
            'method' => 'post',
            'content' => $event,
            'headers' => $headers
        ];

        $request['options'] = [
            'timeout'   => self::WEBHOOK_TIMEOUT,
            'redirects' => self::WEBHOOK_REDIRECTS,
        ];

        return $request;
    }

    protected function webhookSuccessfullyFired($webhook)
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
     * @param $webhook
     */
    protected function webhookFailure($webhook)
    {
        $deleteJobFlag = false;

        if (($this->job->attempts() > self::WEBHOOK_MAXIMUM_ATTEMPTS))
        {
            $deleteJobFlag = true;
        }

        $lastSuccessDifference = $webhook->getTimeDifferenceFromLastSuccessInHour();

        // If (LSA - current time) > 24hrs, mark deactivated.
        if ($lastSuccessDifference > self::WEBHOOK_FAILURE_HOURS)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_DEACTIVATE,
                [
                    'webhook_id'  => $webhook->getId(),
                    'merchant_id' => $webhook->merchant->getId(),
                ]
            );

            $this->disableWebhook($webhook);

            if ($webhook->merchant->isLinkedAccount() === false)
            {
                $this->sendEmail($webhook, 'deactivate');
            }

            $deleteJobFlag = true;
        }
        else
        {
            if ($webhook->merchant->isLinkedAccount() === false)
            {
                $this->sendEmail($webhook, 'failure');
            }
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
                    'data' => $data
                ]);
        }
        else if ($webhook->isActive() === false)
        {
            $webhook = null;
        }

        return $webhook;
    }
}
