<?php


namespace RZP\Services\Aws\Credentials;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Aws\Credentials\Credentials;
use Razorpay\Trace\Facades\Trace;
use Aws\Exception\CredentialsException;
use RZP\Trace\TraceCode;

class InstanceProfileProvider
{
    const SERVER_URI = 'http://169.254.169.254/latest/';

    const CRED_PATH = 'meta-data/iam/security-credentials/';

    private $profile;

    private $client;

    private $cache;

    private $cacheKey;

    private $timeout;

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
        $this->timeout = $config['timeout'] ?? 1.0;

        $this->profile = $config['profile'] ?? null;

        $this->client = new Client(['base_uri' => self::SERVER_URI]);

        $this->cache = new AwsCacheAdapter('file');

        $this->cacheKey = 'aws_cached_credentials';
    }

    public function getProvider()
    {
        return function()
        {
            try
            {
                $found = $this->cache->get($this->cacheKey);

                if ($found !== null)
                {
                    return Promise\promise_for($this->constructCredentials($found));
                }

                if ($this->profile === null)
                {
                    $this->profile = $this->request(self::CRED_PATH);
                }

                $json = $this->request(self::CRED_PATH . $this->profile);

                $result = $this->decodeResult($json);

                $credentials = $this->constructCredentials($result);

                $currentTime = time();

                $ttl = $credentials->getExpiration() - $currentTime;

                $this->cache->set($this->cacheKey, $result,  $ttl);

                return Promise\promise_for($this->constructCredentials($result));
            }
            catch (Exception $e)
            {
                Trace::info(TraceCode::AWS_CACHE_EXCEPTION,
                    ['error' => $e->getMessage()]);

                if ($e instanceof CredentialsException)
                {
                    return new Promise\RejectedPromise($e);
                }

                return new Promise\RejectedPromise(new CredentialsException($e->getMessage()));
            }
        };
    }

    private function constructCredentials(array $input): Credentials
    {
        return new Credentials(
            $input['AccessKeyId'],
            $input['SecretAccessKey'],
            $input['Token'],
            strtotime($input['Expiration'])
        );
    }

    private function request(string $url): string
    {
        $response = $this->client->request('GET', $url);

        return (string) $response->getBody();
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if ($result['Code'] !== 'Success')
        {
            throw new CredentialsException('Unexpected instance profile ' . 'response code: ' . $result['Code']);
        }

        return $result;
    }
}

