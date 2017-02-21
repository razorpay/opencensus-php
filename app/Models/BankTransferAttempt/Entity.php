<?php

namespace RZP\Models\BankTransferAttempt;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ENTITY_TYPE           = 'entity_type';
    const ENTITY_ID             = 'entity_id';
    const STATUS                = 'status';
    const UTR                   = 'utr';
    const REMARKS               = 'remarks';
    const DATE_TIME             = 'date_time';
    const CMS_REF_NO            = 'cms_ref_no';

    protected $entity = 'bank_transfer_attempt';

    protected $visible = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::STATUS,
        self::UTR,
        self::REMARKS,
        self::DATE_TIME,
        self::CMS_REF_NO,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::UTR
    ];

    public function source()
    {
        // THink about this. this needs to be many and may be belong ttypes
        return $this->morphTo('source', 'entity_type', 'entity_id');
    }

    /**
     * Associates the entity id and validates that the entity id is unique.
     * @param $entity
     */
    public function sourceAssociate($entity)
    {
        $this->source()->associate($entity);

        $entity->bankTransferAttempt()->associate($this);
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
}