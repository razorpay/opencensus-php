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

use Http\Discovery\HttpClientDiscovery;
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Exception\ServerErrorException;
use Http\Client\Exception\HttpException;

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

    const KNOWN_ERRORS = [
        'unable to get local issuer certificate',
        'empty reply from server',
        'ssl certificate problem: certificate has expired',
        '<url> malformed',
        'server error response',
        'too many redirects',
    ];

    /**
     * We keep it internally as 20 seconds
     * but publicly we say it's only 5 seconds.
     */
    const WEBHOOK_TIMEOUT = 20;

    const WEBHOOK_REDIRECTS = 3;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->repo = new Repository;
    }

    /**
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {
        $this->job = $job;

        $this->mode = $data['mode'];

        $this->event = $data['event'];

        $webhook = $this->getWebhook($data);

        if ($webhook->isActive() === false)
        {
            $job->delete();

            return;
        }

        $request = $this->getRequestArray($data['event'], $webhook);

        $success = $this->sendRequest($request, $webhook);

        $this->updateWebhookPostFiring($success, $webhook);
    }

    protected function updateWebhookPostFiring($success, $webhook)
    {
        if ($success === true)
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

        $req = $factory->createRequest('POST', $request['url'], $request['headers'], $request['content']);

        $httpClient = $this->createHttpClient();

        $response = $httpClient->sendRequest($req);

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

    public function getClient()
    {
        if ($this->client === null)
        {
            $this->client = HttpClientDiscovery::find();
        }

        return $this->client;
    }

    public function sendRequest($request, $webhook)
    {
        $success = true;
        $response = null;

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            [
                'webhook_id'  => $webhook->getId(),
                'merchant_id' => $webhook->merchant->getId(),
                'request'     => $request
            ]);

        try
        {
            $response = $this->makeRequest($request);
        }
        catch (ClientErrorException $e)
        {
            $response = $e->getResponse();

            $this->errorMessage = 'Client error: '. $response->getReasonPhrase();

            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'exception'         => $this->errorMessage,
                    'response_code'     => $response->getStatusCode(),
                    'response_headers'  => $response->getHeaders()
                ]);

            return false;
        }
        catch (ServerErrorException $e)
        {
            $response = $e->getResponse();

            $this->errorMessage = 'Server error: '. $response->getReasonPhrase();

            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'exception'         => $this->errorMessage,
                    'response_code'     => $response->getStatusCode(),
                    'response_headers'  => $response->getHeaders()
                ]);

            return false;
        }
        catch (HttpException $e)
        {
            $response = $e->getResponse();

            $this->errorMessage = 'Some error occurred: '. $response->getReasonPhrase();

            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'exception'         => $this->errorMessage,
                    'response_code'     => $response->getStatusCode(),
                    'response_headers'  => $response->getHeaders()
                ]);

            return false;
        }

        if ($response->getStatusCode() !== 200)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'response_code'     => $response->getStatusCode(),
                    'response_body'     => $response->getReasonPhrase(),
                    'response_headers'  => $response->getHeaders()
                ]
            );

            $this->errorMessage = $response->getReasonPhrase();

            $success = false;
        }
        else
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRED,
                [
                    'webhook_id'        => $webhook->getId(),
                    'merchant_id'       => $webhook->merchant->getId(),
                    'response_code'     => $response->getStatusCode(),
                    'response_headers'  => $response->getHeaders()
                ]);
        }

        return $success;
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
        $this->repo->setLastSuccessfulAt($webhook);

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
        $job = $this->job;

        $sendFailureEmail = 1;
        $jobDeleted = 0;

        if (($job->attempts() > self::WEBHOOK_MAXIMUM_ATTEMPTS))
        {
            $job->delete();
            $jobDeleted = 1;
        }

        $lastSuccessfulAt = $webhook->getLastSuccessfulAt();
        $currentTime = time();

        if ($lastSuccessfulAt !== null)
        {
            $differenceHours = ($currentTime - $lastSuccessfulAt) / 3600;

            // If (LSA - current time) > 24hrs, mark deactivated.
            if (($differenceHours > self::WEBHOOK_FAILURE_HOURS))
            {
                $this->trace->info(
                    TraceCode::WEBHOOK_DEACTIVATE,
                    [
                        'webhook_id'  => $webhook->getId(),
                        'merchant_id' => $webhook->merchant->getId(),
                    ]
                );

                $webhook->deactivate();

                $this->repo->saveOrFail($webhook);

                $this->sendEmail($webhook,'deactivate');

                // Webhook is now inactive
                // So let's just delete the job
                if ($jobDeleted == 0)
                {
                    $job->delete();
                }

                $sendFailureEmail = 0;
            }
        }

        if ($sendFailureEmail === 1)
        {
            $this->sendEmail($webhook,'failure');

            // Attempt again after 1 hour
            $job->release(3600);
        }
    }

    protected function getWebhook($data)
    {
        $mode = $data['mode'];

        $webhook = $this->repo
                        ->connection($mode)
                        ->find($data['webhook_id']);

        if ($webhook === null)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRING,
                ['data' => $data]);

            $this->job->delete();
        }

        return $webhook;
    }

    protected function isKnownRequestsException($e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        foreach (self::KNOWN_ERRORS as $errorMessage)
        {
            if (strpos($msg, $errorMessage) !== false)
            {
                return true;
            }
        }
        return false;
    }
}
