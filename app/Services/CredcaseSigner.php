<?php

namespace RZP\Services;

use RZP\Tests\Unit\Services\CredcaseSignerTest;
use Throwable;
use Socket\Raw\Factory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connectors\PredisConnector;

use Razorpay\Trace\Logger;

use RZP\Trace\TraceCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\RuntimeException;

class CredcaseSigner
{
    // MOCK_SIGNATURE is returned if mock is true.
    const MOCK_SIGNATURE                        = '0000000000000000000000000000000000000000000000000000000000000000';

    const METRIC_SIGN_REQUESTS_TOTAL            = 'signer_requests_total'; // Labels: by:api|credcase_signer.
    const METRIC_SIGN_FAILURE_TOTAL             = 'signer_failure_total';
    const METRIC_SIGN_LATENCY_SECS              = 'signer_latency_seconds.histogram'; // Labels: by:api|credcase_signer.
    const METRIC_SIGN_REDIS_KEY_NOT_FOUND_TOTAL = 'signer_redis_key_not_found_total';
    const METRIC_SIGN_REDIS_ERROR_TOTAL         = 'signer_redis_error_total';
    const METRIC_SIGN_REDIS_GET_LATENCY_SECS    = 'signer_redis_get_latency_seconds.histogram';

    const CREDCASE_CACHE_KEY_PREFIX             = 'credcase:ks';
    const CREDCASE_CACHE_KEY_VERSION            = 'v1';

    /** @var \RZP\Services\RazorXClient */
    protected $razorx;

    /** @var \RZP\Http\BasicAuth\BasicAuth */
    protected $ba;

    /** @var \Razorpay\Trace\Logger */
    protected $trace;

    /** @var \Illuminate\Redis\Connections\Connection */
    protected static $redis;

    /** @var array */
    protected $config;

    public function __construct(Connection $redis = null)
    {
        $this->razorx = app('razorx');
        $this->ba     = app('basicauth');
        $this->trace  = app('trace');
        static::$redis  = $redis ?: $this->instantiatePredisConnectionOnce();
        $this->config = app('config')->get('services.credcase_signer');
    }

    /**
     * Instantiates predis connection object once and sets as static property.
     *
     * Why? Fixes predis instantiation for credcase signer wrt 6.x upgrade. It
     * can be reverted if laravel/framework#36762 gets accepted. Version 6.x
     * stopped supporting `username` in redis connection config after
     * laravel/framework#33892 which got fixed later in 8.x with laravel/framework#36299.
     *
     * @return Connection
     */
    protected function instantiatePredisConnectionOnce(): Connection
    {
        if (static::$redis === null)
        {
            $config = app('config')->get('database.redis');
            static::$redis = (new PredisConnector)->connect($config['credcase_signer'], $config['options'] ?? []);
        }

        return static::$redis;
    }

    /**
     * @param  string      $payload
     * @param  string|null $publicKey
     * @return string
     */
    public function sign(string $payload, string $publicKey = null): string
    {
        // Calls credcase's flow with {public key from auth, payload}, and
        // fallbacks to existing flow (ref BasicAuth's sign) on any failure.

        // If publicKey argument is null then attempts to use key available in
        // auth, but does this for credcase flow only because eventually it will
        // be like this. In current flow, there is more/different logic to
        // find key basis various auth types etc.
        $publicKeyDefaulted = $publicKey ?: $this->ba->getPublicKey();

        if ($this->shouldSignByCredcase($publicKeyDefaulted) === true)
        {
            try
            {
                return $this->signByCredcase($payload, $publicKeyDefaulted);
            }
            catch (Throwable $e)
            {
                $this->trace->count(self::METRIC_SIGN_FAILURE_TOTAL);
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::CREDCASE_SIGNER_ERROR,
                    ['key' => $publicKeyDefaulted]
                );
            }
        }

        return $this->signByApi($payload, $publicKey);
    }

    /**
     * @param  string|null $publicKey
     * @return boolean
     */
    protected function shouldSignByCredcase(string $publicKey = null): bool
    {
        $merchantId = $this->ba->getMerchantId();

        if ((app()->runningUnitTests() === true) and !in_array($publicKey, CredcaseSignerTest::KEYS)) {
            return false;
        }

        // Currently, Credcase signer supports merchant keys, partner auth keys and oauth public keys
        // It will also support {rzp_mode_mid} in the future
        if (($publicKey !== null)
            and ((preg_match(BasicAuth::KEY_REGEX, $publicKey) === 1
            and str_ends_with($publicKey, $merchantId) === false)
            or preg_match(BasicAuth::PARTNER_KEY_REGEX, $publicKey) === 1)
            or preg_match(BasicAuth::OAUTH_KEY_REGEX, $publicKey) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param  string $payload
     * @param  string $publicKey
     * @return string
     * @throws RuntimeException
     */
    protected function signByCredcase(string $payload, string $publicKey): string
    {
        // Mock only applies to non production environment!
        if (($this->config['mock'] === true) and (app()->isEnvironmentProduction() === false))
        {
            return self::MOCK_SIGNATURE;
        }


        $this->trace->debug(TraceCode::CREDCASE_SIGNER_INVOKED, compact('payload', 'publicKey'));
        $this->trace->count(self::METRIC_SIGN_REQUESTS_TOTAL, ['by' => 'credcase_signer']);

        $startAt = microtime(true);
        try
        {
            // Credcase Cache stores (key: secret) mapping.
            $publicKeyNameInCache = $publicKey;

            // If key is a partner auth key, strip account id suffix from key before forming the cache key
            if (preg_match(BasicAuth::PARTNER_KEY_REGEX, $publicKey) === 1)
                $publicKeyNameInCache = substr($publicKey, 0, strpos($publicKey, "-acc_"));
            // If key is a public oauth key, extract only the key from the public key string
            else if (preg_match(BasicAuth::OAUTH_KEY_REGEX, $publicKey, $matches) === 1)
                $publicKeyNameInCache = $matches[1];

            $cacheKey = self::CREDCASE_CACHE_KEY_PREFIX . ':' . self::CREDCASE_CACHE_KEY_VERSION . ':' . $publicKeyNameInCache;

            $encryptedSecret = null;

            // Tries max 2 times to get encrypted secret from redis.
            $numOfMaxTries = 2;
            while ($numOfMaxTries--)
            {
                try
                {
                    $redisGetStartAt = microtime(true);
                    $encryptedSecret = static::$redis->get($cacheKey);
                    $this->trace->histogram(self::METRIC_SIGN_REDIS_GET_LATENCY_SECS, microtime(true) - $redisGetStartAt);

                    if ($encryptedSecret === null)
                    {
                        $this->trace->count(self::METRIC_SIGN_REDIS_KEY_NOT_FOUND_TOTAL);
                        throw new RuntimeException('Encrypted secret not found in redis');
                    }

                    break;
                }
                catch (Throwable $e)
                {
                    $this->trace->count(self::METRIC_SIGN_REDIS_ERROR_TOTAL);
                    $this->trace->traceException(
                        $e, Logger::ERROR, TraceCode::CREDCASE_SIGNER_REDIS_ERROR, ['key' => $publicKey]);
                }
            }

            $secret = decrypt(hex2bin($encryptedSecret), $this->config['private_key']);

            return hash_hmac('sha256', $payload, $secret);
        }
        finally
        {
            $this->trace->histogram(self::METRIC_SIGN_LATENCY_SECS, microtime(true) - $startAt, ['by' => 'credcase_signer']);
        }
    }

    /**
     * @param  string $payload
     * @param  string|null $publicKey
     * @return string
     */
    protected function signByApi(string $payload, string $publicKey = null): string
    {
        $this->trace->count(self::METRIC_SIGN_REQUESTS_TOTAL, ['by' => 'api']);

        $startAt = microtime(true);
        $signature = $this->ba->sign($payload, $publicKey);
        $this->trace->histogram(self::METRIC_SIGN_LATENCY_SECS, microtime(true) - $startAt, ['by' => 'api']);

        return $signature;
    }
}

/**
 * Decrypts value using AES-256-GCM cipher. It corresponds to the encryption logic used in credcase.
 *
 * Refs:
 * - https://github.com/razorpay/credcase/blob/master/internal/crypto/crypto.go
 * - https://github.com/luke-park/SecureCompatibleEncryptionExamples/blob/a1cd6440c4d12038de1a854ab504102d73d0500b/PHP/SCEE.php#L47-L55
 * - https://golang.org/src/crypto/cipher/gcm.go
 *
 * @param  string $ciphertextAndNonce
 * @param  string $key
 * @return string
 * @throws RuntimeException
 */
function decrypt(string $ciphertextAndNonce, string $key): string
{
    $algorithmName = 'aes-256-gcm';
    $algorithmNonceSize = 12;
    $algorithmTagSize = 16;

    $nonce = substr($ciphertextAndNonce, 0, $algorithmNonceSize);
    $ciphertext = substr($ciphertextAndNonce, $algorithmNonceSize, strlen($ciphertextAndNonce) - $algorithmNonceSize - $algorithmTagSize);
    $tag = substr($ciphertextAndNonce, strlen($ciphertextAndNonce) - $algorithmTagSize);

    $value = openssl_decrypt($ciphertext, $algorithmName, $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($value === false)
    {
        throw new RuntimeException('Could not decrypt ciphertext');
    }

    return $value;
}
