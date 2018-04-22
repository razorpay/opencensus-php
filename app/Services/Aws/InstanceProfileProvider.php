<?php
namespace RZP\Services\Aws;

use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Aws\Credentials\Credentials;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Trace\Facades\Trace as TraceFacade;
use RZP\Trace\TraceCode;

/**
 * Credential provider that provides credentials from the EC2 metadata server.
 */
class InstanceProfileProvider
{
    const SERVER_URI = 'http://169.254.169.254/latest/';
    const CRED_PATH = 'meta-data/iam/security-credentials/';

    /** @var string */
    private $profile;

    /** @var callable */
    private $client;

    private $cache;

    private $cacheKey;

    /**
     * The constructor accepts the following options:
     *
     * - timeout: Connection timeout, in seconds.
     * - profile: Optional EC2 profile name, if known.
     *
     * @param array $config Configuration options.
     */
    public function __construct(array $config = [])
    {
        $this->timeout = isset($config['timeout']) ? $config['timeout'] : 1.0;
        $this->profile = isset($config['profile']) ? $config['profile'] : null;
        $this->client = new Client(['base_uri' => self::SERVER_URI]);
        $this->cache = new AwsCacheAdapter('file');
        $this->cacheKey = 'aws_cached_credentials';
        $this->trace = TraceFacade::getFacadeRoot();
    }

    public function getProvider() {
        return function() {
            try {
                $found = $this->cache->get($this->cacheKey);
                if ($found) {
                    return Promise\promise_for($this->constructCredentials($found));
                }

                if (!$this->profile) {
                    $this->profile = $this->request(self::CRED_PATH);
                }

                $json = $this->request(self::CRED_PATH . $this->profile);
                $result = $this->decodeResult($json);

                $this->cache->set($this->cacheKey, $result, 120);
                return Promise\promise_for($this->constructCredentials($result));
            } catch (Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::AWS_CACHE_FAILURE);

                if ($e instanceof CredentialsException) {
                    return new Promise\RejectedPromise($e);
                }
                return new Promise\RejectedPromise(new CredentialsException($e->getMessage()));
            }
        };
    }

    private function constructCredentials(array $input)
    {
        return new Credentials($input['AccessKeyId'],
            $input['SecretAccessKey'],
            $input['Token'],
            strtotime($input['Expiration']));
    }

    private function isExpired(array $input)
    {
        $expires = strtotime($input['Expiration']);
        return $expires !== null && time() >= $expires;
    }

    /**
     * @param string $url
     * @return PromiseInterface Returns a promise that is fulfilled with the
     *                          body of the response as a string.
     */
    private function request($url)
    {
        $response = $this->client->request('GET', $url);
        return (string) $response->getBody();
    }

    private function createErrorMessage($previous)
    {
        return "Error retrieving credentials from the instance profile "
            . "metadata server. ({$previous})";
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if ($result['Code'] !== 'Success') {
            throw new CredentialsException('Unexpected instance profile '
                .  'response code: ' . $result['Code']);
        }

        return $result;
    }
}