<?php

namespace RZP\Models\Key;

use Crypt;
use Request;
use Twirp\Error;
use Twirp\Context;
use Razorpay\Trace\Logger;
use Rzp\Common\Mode\V1\Mode;
use Rzp\Credcase\Migrate\V1\MigrateAPIClient;
use Rzp\Credcase\Migrate\V1\ExpireApiKeyRequest;
use Rzp\Credcase\Migrate\V1\RotateApiKeyRequest;
use Rzp\Credcase\Migrate\V1\MigrateApiKeyRequest;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;

class Credcase
{
    const METRIC_CREDCASE_REQUEST_FAILED_TOTAL = 'credcase_request_failed_total';

    /** @var Logger */
    protected $trace;

    /**
     * Dual write can be kept disabled for development environment and existing tests.
     * @var boolean
     */
    protected $dualWriteEnabled = true;

    /** @var \RZP\Services\RazorXClient */
    protected $razorx;

    /** @var RetriableMigrateAPIClient */
    protected $migrateApiClient;

    /**
     * @see \Twirp\Context
     * @var array|null
     */
    protected $apiClientCtx;

    public function __construct()
    {
        $this->trace = app('trace');

        $config = app('config')->get('services.credcase');
        $this->dualWriteEnabled = $config['dual_write_enabled'];

        $this->razorx = app('razorx');

        $host = $config['host'];
        // Http client comes as injected service, making able to replace with mock http client for unit tests.
        // It can also be pre-configured with timeout and other options.
        $httpClient = app('credcase_http_client');
        $migrateApiClient = new MigrateAPIClient($host, $httpClient);
        $this->migrateApiClient = new RetriableMigrateAPIClient($migrateApiClient);

        $auth = 'Basic '.base64_encode($config['user'].':'.$config['password']);
        $headers = ['Authorization' => $auth, 'X-Request-ID' => Request::getTaskId()];
        $this->apiClientCtx = Context::withHttpRequestHeaders([], $headers);
    }

    /**
     * @param  Entity $key
     * @param  string $mode
     * @return void
     * @throws \Twirp\Error
     */
    public function migrate(Entity $key, string $mode)
    {
        // Razorx check would be removed, but the flag in config would still exist, refer comment above ^.
        $dualWriteEnabledViaRazorx = $this->razorx->getTreatment(
            $key->getMerchantId(), RazorxTreatment::CREDCASE_DUAL_WRITE_ENABLED, $mode) === 'on';
        if (($this->dualWriteEnabled and $dualWriteEnabledViaRazorx) === false)
        {
            return;
        }

        return $this->migrateWithoutRazorxCheck($key, $mode);
    }

    /**
     * @see Credcase's migrate function. This is used with migration where we do not want to check for razorx.
     *
     * @param  Entity $key
     * @param  string $mode
     * @return void
     * @throws \Twirp\Error
     */
    public function migrateWithoutRazorxCheck(Entity $key, string $mode)
    {
        $this->trace->info(TraceCode::CREDCASE_REQUEST_MIGRATE, ['key_id' => $key->getId(), 'mode' => $mode]);

        $req = newMigrateApiKeyRequest($key, $mode);

        $this->migrateApiClient->MigrateApiKey($this->apiClientCtx, $req);
    }

    /**
     * @param  Entity $oldKey
     * @param  Entity $newKey
     * @param  string $mode
     * @return void
     * @throws \Twirp\Error
     */
    public function rotate(Entity $oldKey, Entity $newKey, string $mode)
    {
        // Razorx check would be removed, but the flag in config would still exist, refer comment above ^.
        $dualWriteEnabledViaRazorx = $this->razorx->getTreatment(
            $oldKey->getMerchantId(), RazorxTreatment::CREDCASE_DUAL_WRITE_ENABLED, $mode) === 'on';
        if (($this->dualWriteEnabled and $dualWriteEnabledViaRazorx) === false)
        {
            return;
        }

        $this->trace->info(TraceCode::CREDCASE_REQUEST_ROTATE, ['old_key_id' => $oldKey->getId(), 'new_key_id' => $newKey->getId(), 'mode' => $mode]);

        $expireApiKeyRequest = new ExpireApiKeyRequest;
        $expireApiKeyRequest->setId($oldKey->getId());
        $expireApiKeyRequest->setExpiredAt($oldKey->getExpiredAt());

        $migrateApiKeyRequest = newMigrateApiKeyRequest($newKey, $mode);

        $req = new RotateApiKeyRequest;
        $req->setExpireKey($expireApiKeyRequest);
        $req->setCreateKey($migrateApiKeyRequest);

        $this->migrateApiClient->RotateApiKey($this->apiClientCtx, $req);
    }
}

/**
 * @param  Entity $key
 * @param  string $mode
 * @return MigrateApiKeyRequest
 */
function newMigrateApiKeyRequest(Entity $key, string $mode): MigrateApiKeyRequest
{
    $req = new MigrateApiKeyRequest;
    $req->setId($key->getId());
    $req->setSecret(Crypt::decrypt($key->getSecret()));
    $req->setMode(constant(Mode::class.'::'.$mode));
    $req->setMerchantId($key->getMerchantId());
    $req->setCreatedAt($key->getCreatedAt());
    $req->setExpiredAt($key->getExpiredAt() ?: 0);

    return $req;
}

class RetriableMigrateAPIClient
{
    /** @var MigrateAPIClient */
    protected $client;

    public function __construct(MigrateAPIClient $client)
    {
        $this->client = $client;
    }

    public function __call(string $name, array $arguments)
    {
        $exception   = null; // Holds last exception.
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                return $this->client->$name(...$arguments);
            }
            catch (Error $e)
            {
                app('trace')->traceException($e, null, TraceCode::CREDCASE_REQUEST_FAILED);
                app('trace')->count(Credcase::METRIC_CREDCASE_REQUEST_FAILED_TOTAL, ['method' => $name]);
                $exception = $e;
            }
        }

        // Wraps \Twirp\Error as api's error and returns.
        throw new ServerErrorException(
            'Failed to complete request',
            ErrorCode::SERVER_ERROR_CREDCASE_REQUEST_FAILED,
            null,
            $exception
        );
    }
}
