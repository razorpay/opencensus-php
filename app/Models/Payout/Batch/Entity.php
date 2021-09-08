<?php

namespace RZP\Models\Payout\Batch;

use RZP\Constants;
use RZP\Models\Base\PublicEntity as BasePublicEntity;

class Entity extends BasePublicEntity
{
    const BATCH_ID = 'batch_id';

    // The reference ID will be provided by the merchant hitting the API, this will be reflected in the
    // "notes" field in each payout created within this batch
    const REFERENCE_ID = 'reference_id';

    // This will merely be a copy of the status of the actual batch entity in the batch microservice
    const STATUS = 'status';

    protected $primaryKey = self::BATCH_ID;

    protected $entity = Constants\Entity::PAYOUTS_BATCH;

    const PUBLIC_ENTITY_NAME = 'payouts.batch';

    protected $defaults = [
        self::STATUS => Status::ACCEPTED,
    ];

    protected $fillable = [
        self::REFERENCE_ID,
    ];

    protected $visible = [
        self::BATCH_ID,
        self::MERCHANT_ID,
        self::REFERENCE_ID,
        self::STATUS,
    ];

    protected $public = [
        self::ENTITY,
        self::BATCH_ID,
        self::REFERENCE_ID,
        self::STATUS,
    ];

    protected $publicSetters = [
        self::ENTITY,
        self::BATCH_ID,
        // Added this to return the status in Title Case rather than lowercase
        self::STATUS,
    ];

    //Getters
    public function getId()
    {
        return $this->getKey();
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    //Setters
    public function setId($value)
    {
        if(str_starts_with($value, 'batch_') === true)
        {
            $value = substr($value, strlen('batch_'));
        }

        self::verifyUniqueId($value, true);

        return $this->setAttribute(self::BATCH_ID, $value);
    }

    public function setStatus(string $value)
    {
        return $this->setAttribute(self::STATUS, $value);
    }

    protected function setReferenceId(string $value)
    {
        return $this->setAttribute(self::REFERENCE_ID, $value);
    }

    // Relations
    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    // Public Setters
    public function setPublicEntityAttribute(array &$array)
    {
        $array[static::ENTITY] = self::PUBLIC_ENTITY_NAME;
    }

    public function setPublicBatchIdAttribute(array &$array)
    {
        $array[self::BATCH_ID] = 'batch_' . $this->getId();
    }

    public function setPublicStatusAttribute(array &$array)
    {
        $array[self::STATUS] = ucfirst($this->getStatus());
    }

    // Batch service related functions
    public function updateStatusFromBatchService()
    {
        $status = $this->getStatus();

        // If the batch is already in a terminal state, then no updates are needed
        if (($status === Status::PROCESSED) or
            ($status === Status::FAILED))
        {
            return $status;
        }

        $response = (new Core())->updateEntityFromBatchService($this->getId());

        // Batch service doesn't throw an exception, just returns null if an exception is observed
        if (is_null($response))
        {
            return $status;
        }

        $newStatus = Status::$statusMapBetweenPayoutsBatchAndBatchService[$response[self::STATUS]];

        // If there's no status change, we can save a DB call by not updating the batch entity
        if ($newStatus !== $status)
        {
            $this->setStatus($newStatus);

            $this->saveOrFail();
        }

        return $newStatus;
    }
}
