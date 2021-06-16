<?php

namespace RZP\Models\Key;

use Crypt;
use Request;
use Razorpay\Trace\Logger;
use Rzp\Common\Mode\V1\Mode;
use Rzp\Credcase\Migrate\V1\ExpireApiKeyRequest;
use Rzp\Credcase\Migrate\V1\RotateApiKeyRequest;
use Rzp\Credcase\Migrate\V1\MigrateApiKeyRequest;

use RZP\Trace\TraceCode;

class Credcase
{

    /** @var Logger */
    protected $trace;

    /**
     * used for inserting adding outbox jobs within a transactional context.
     * @var \Razorpay\Outbox\Job\Core
     */
    protected $outbox;

    /**
     * Dual write can be kept disabled for development environment and existing tests.
     * @var boolean
     */
    protected $dualWriteEnabled = true;

    public function __construct()
    {
        $this->trace = app('trace');

        $this->outbox = app('outbox');

        $config = app('config')->get('services.credcase');
        $this->dualWriteEnabled = $config['dual_write_enabled'];
    }

    /**
     * @param  Entity $key
     * @param  string $mode
     * @return void
     */
    public function migrate(Entity $key, string $mode)
    {
        if ($this->dualWriteEnabled === false)
        {
            return;
        }

        $this->trace->info(TraceCode::CREDCASE_OUTBOX_REQUEST_MIGRATE, ['key_id' => $key->getId(), 'mode' => $mode]);

        $req = newMigrateApiKeyRequest($key, $mode);

        $this->outbox->send(OutboxHandler::MIGRATE, $req);
    }

    /**
     * @param  Entity $oldKey
     * @param  Entity $newKey
     * @param  string $mode
     * @return void
     */
    public function rotate(Entity $oldKey, Entity $newKey, string $mode)
    {
        if ($this->dualWriteEnabled === false)
        {
            return;
        }

        $this->trace->info(TraceCode::CREDCASE_OUTBOX_REQUEST_ROTATE, ['old_key_id' => $oldKey->getId(), 'new_key_id' => $newKey->getId(), 'mode' => $mode]);

        $expireApiKeyRequest = new ExpireApiKeyRequest;
        $expireApiKeyRequest->setId($oldKey->getId());
        $expireApiKeyRequest->setExpiredAt($oldKey->getExpiredAt());

        $migrateApiKeyRequest = newMigrateApiKeyRequest($newKey, $mode);

        $req = new RotateApiKeyRequest;
        $req->setExpireKey($expireApiKeyRequest);
        $req->setCreateKey($migrateApiKeyRequest);

        $this->outbox->send(OutboxHandler::ROTATE, $req);
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
