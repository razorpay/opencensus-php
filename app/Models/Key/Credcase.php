<?php

namespace RZP\Models\Key;

use Crypt;
use Request;
use Razorpay\Trace\Logger;
use Rzp\Common\Mode\V1\Mode;

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
        $req = array(
            "expire_key" => newExpireApiKeyRequest($oldKey),
            "create_key" => newMigrateApiKeyRequest($newKey, $mode),
        );

        $this->outbox->send(OutboxHandler::ROTATE, $req);
    }
}

/**
 * @param Entity $key
 * @param string $mode
 *
 * @return array
 */
function newMigrateApiKeyRequest(Entity $key, string $mode)
{
    $req                      = array();
    $req[Entity::ID]          = $key->getId();
    $req[Entity::SECRET]      = Crypt::decrypt($key->getSecret());
    $req["mode"]              = constant(Mode::class . '::' . $mode);
    $req[Entity::MERCHANT_ID] = $key->getMerchantId();
    $req[Entity::CREATED_AT]  = $key->getCreatedAt();
    if (!empty($key->getExpiredAt())) {
        $req[Entity::EXPIRED_AT] = $key->getExpiredAt();
    }

    return $req;
}

/**
 * @param Entity $oldKey
 *
 * @return array
 */
function newExpireApiKeyRequest(Entity $oldKey)
{
    return array(
        Entity::ID         => $oldKey->getId(),
        Entity::EXPIRED_AT => $oldKey->getExpiredAt(),
    );
}
