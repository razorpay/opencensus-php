<?php

namespace RZP\Services\Aws\Credentials;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Razorpay\Trace\Logger;
use Aws\Credentials\Credentials;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise\PromiseInterface;

use RZP\Trace\TraceCode;

/**
 * Credential provider that provides credentials from the EC2 metadata server.
 * We couldn't extend the sdk's provider as it is very private.
 *
 * Ref: Aws\Credentials\InstanceProfileProvider
 */
class InstanceProfileProvider
{
    const SERVER_URI = 'http://169.254.169.254/latest/';
    const CRED_PATH  = 'meta-data/iam/security-credentials/';

    /**
     * @var string
     */
    protected $profile;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var string
     */
    protected $cacheTtl;

    /**
     * E.g. file, redis etc
     * @var string
     */
    protected $cacheStore;

    /**
     * Options:
     * - timeout:       Connection timeout, in seconds.
     * - profile:       Optional EC2 profile name, if known.
     * - cache_store:   Credentials would be cached in this laravel cache store, e.g. file, redis
     * - cache_key:     Cache key to be used to cache the credentials
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->timeout    = $config['timeout'] ?? 1.0;
        $this->profile    = $config['profile'] ?? null;

        $this->cacheKey   = $config['cache_key'] ?? 'aws_cached_credentials';
        $this->cacheTtl   = $config['cache_ttl'] ?? 15;
        $this->cacheStore = $config['cache_store'] ?? 'file';

        // Initializes guzzle client
        $this->client     = new Client(['base_uri' => self::SERVER_URI, 'timeout' => $this->timeout]);
    }

    /**
     * Returns the provider caller
     * @return Closure
     */
    public function getProvider(): Closure
    {
        /**
         * @return PromiseInterface
         */
        return function()
        {
            try
            {
                $result = $this->getCache()->remember(
                            $this->cacheKey,
                            $this->cacheTtl,
                            function ()
                            {
                                $this->getTrace()->debug(TraceCode::INSTANCE_PROFILE_PROVIDER_CACHE_MISS, []);

                                $this->profile = $this->profile ?: $this->request(self::CRED_PATH);
                                $response      = $this->request(self::CRED_PATH . $this->profile);

                                return $this->decodeResult($response);
                            });

                return Promise\promise_for($this->getCredentialsInstance($result));
            }
            catch (\Throwable $e)
            {
                // Sdk expects the exception to be instance of CredentialsException
                if ($e instanceof CredentialsException === false)
                {
                    $e = new CredentialsException($e->getMessage(), 0, $e);
                }

                return new Promise\RejectedPromise($e);
            }
        };
    }

    /**
     * Constructs Credentials instance out of given input
     * @param  array  $input
     * @return Credentials
     */
    protected function getCredentialsInstance(array $input): Credentials
    {
        return new Credentials(
                    $input['AccessKeyId'],
                    $input['SecretAccessKey'],
                    $input['Token'],
                    strtotime($input['Expiration']));
    }

    /**
     * Makes request to given url and returns string body
     * @param  string $url
     * @return string
     */
    protected function request(string $url): string
    {
        return (string) $this->client->request('GET', $url)->getBody();
    }

    /**
     * Decodes string response from aws api
     * @param  string $response
     * @return array
     * @throws CredentialsException
     */
    protected function decodeResult(string $response): array
    {
        $result = json_decode($response, true);

        if ($result['Code'] !== 'Success')
        {
            throw new CredentialsException('Unexpected instance profile response code: ' . $result['Code']);
        }

        return $result;
    }

    protected function getCache(): Repository
    {
        return app('cache')->store($this->cacheStore);
    }

    protected function getTrace(): Logger
    {
        return app('trace');
    }
}
