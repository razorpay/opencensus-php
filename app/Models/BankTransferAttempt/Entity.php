<?php

namespace RZP\Models\BankTransferAttempt;

use RZP\Models\Base;
use RZP\Models\BankAccount;

class Entity extends Base\PublicEntity
{
    const ENTITY_TYPE           = 'entity_type';
    const ENTITY_ID             = 'entity_id';
    const BANK_ACCOUNT_ID       = 'bank_account_id';
    const CHANNEL               = 'channel';
    const VERSION               = 'version';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const STATUS                = 'status';
    const UTR                   = 'utr';
    const REMARKS               = 'remarks';
    const DATE_TIME             = 'date_time';
    const CMS_REF_NO            = 'cms_ref_no';
    const FAILURE_REASON        = 'failure_reason';
    const BATCH_TRANSFER_ID     = 'batch_transfer_id';

    protected static $sign = 'bta';

    protected $entity = 'bank_transfer_attempt';

    protected $fillable = [
        self::ENTITY_ID,
        self::CHANNEL,
        self::VERSION,
        self::STATUS,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::BANK_ACCOUNT_ID,
        self::CHANNEL,
        self::VERSION,
        self::BANK_STATUS_CODE,
        self::STATUS,
        self::UTR,
        self::REMARKS,
        self::DATE_TIME,
        self::CMS_REF_NO,
        self::FAILURE_REASON,
        self::BATCH_TRANSFER_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::STATUS,
        self::UTR,
    ];

    public function source()
    {
        $entityType = $this->getAttribute(self::ENTITY_TYPE);

        Type::validateType($entityType);

        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    /**
     * Associates the entity id and validates that the entity id is unique.
     * @param $entity
     */
    public function sourceAssociate($entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->source()->associate($entity);
    }

    public function bankAccount()
    {
        return $this->belongsTo(
                    'RZP\Models\BankAccount\Entity',
                    self::BANK_ACCOUNT_ID,
                    BankAccount\Entity::ID);
    }

    public function batchTransfer()
    {
        return $this->belongsTo('RZP\Models\Settlement\Batch\Entity');
    }

    // ------------------------------- getters ---------------------------------

    public function getRemarks()
    {
        return $this->getAttribute(self::remarks);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getVersion()
    {
        return $this->getAttribute(self::VERSION);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    // ------------------------------- setters ---------------------------------

    public function setRemarks($remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setEntityType($type)
    {
        $this->setAttribute(self::ENTITY_TYPE, $type);
    }

    public function setEntityId($id)
    {
        $this->setAttribute(self::ENTITY_ID, $id);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setBankStatusCode($code)
    {
        $this->setAttribute(self::BANK_STATUS_CODE, $code);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setCmsRefNo($refNo)
    {
        $this->setAttribute(self::CMS_REF_NO, $refNo);
    }

    public function setDateTime($dateTime)
    {
        $this->setAttribute(self::DATE_TIME, $dateTime);
    }

    // ------------------------------ modifiers --------------------------------

    protected function setRemarksAttribute($remarks)
    {
        $this->attributes[self::REMARKS] = substr($remarks, 0, 255);
    }

    protected function setDateTimeAttribute($dateTime)
    {
        $this->attributes[self::DATE_TIME] = substr($dateTime, 0, 255);
    }

    protected function setCmsRefNoAttribute($refNo)
    {
        $this->attributes[self::CMS_REF_NO] = substr($refNo, 0, 255);
    }

    // -------------------------------- methods --------------------------------

    public function isStatusCreated()
    {
        return $this->getStatus() === Status::CREATED;
    }

    public function isPendingReconciliation()
    {
        return $this->isStatusCreated();
    }

    public function isStatusFailed()
    {
        return $this->getStatus() === Status::FAILED;
    }
}