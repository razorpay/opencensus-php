<?php

namespace RZP\Models\Consumer;

use Exception;
use Request;
use Razorpay\Trace\Logger;
use Rzp\Credcase\Consumer\V1\Consumer;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;
use RZP\Trace\TraceCode;
use Twirp\Context;
use Twirp\Error;

use Rzp\Credcase\Consumer\V1\ConsumerAPIClient;

class Credcase
{

    private $context;

    /**
     * @var Logger
     */
    private $trace;

    /**
     * @var ConsumerAPIClient
     */
    private $client;

    public function __construct()
    {
        $this->trace  = app('trace');
        $config       = app('config')->get('services.credcase');
        $httpClient   = app('credcase_http_client');
        $this->client = new ConsumerAPIClient($config['host'], $httpClient);

        // Set default headers twirp context.
        $auth          = 'Basic ' . base64_encode($config['user'] . ':' . $config['password']);
        $headers       = ['Authorization' => $auth, 'X-Request-ID' => Request::getTaskId()];
        $this->context = Context::withHttpRequestHeaders([], $headers);
    }

    /**
     * @param string $ownerId
     * @param string $ownerType
     * @param string $domain
     * @param array  $meta
     *
     * @throws ServerErrorException
     * @throws Exception
     */
    public function create(string $ownerId, string $ownerType, string $domain, array $meta)
    {
        $consumer = new Consumer;
        $consumer->setOwnerId($ownerId);
        $consumer->setOwnerType($ownerType);
        $consumer->setMeta($meta);
        $consumer->setDomain($domain);

        $debug = ["owner_id" => $ownerId, "owner_type" => $ownerType, "meta" => $meta, "domain" => $domain];
        try {
            $f = function () use ($consumer) {
                $this->client->CreateConsumer($this->context, $consumer);
            };
            $this->attemptWithRetry($f, 1);
        } catch (Exception $e) {
            $this->trace->traceException($e, null, TraceCode::CREDCASE_REQUEST_FAILED, $debug);
            throw new ServerErrorException('failed to complete request',
                ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED, $debug, $e);
        }
    }

    /**
     * Attempt to call the callable $f with maximum $count retries in case of failures.
     *
     * @param callable $f
     * @param int      $count
     *
     * @return mixed
     * @throws Exception
     */
    private function attemptWithRetry(callable $f, int $count)
    {
        while (true) {
            $count -= 1;
            try {
                return $f();
            } catch (Exception $e) {
                // Retries exhausted.
                if ($count < 0) {
                    throw $e;
                }

                // Backoff before retrying.
                sleep(0.2);
            }
        }
    }
}
