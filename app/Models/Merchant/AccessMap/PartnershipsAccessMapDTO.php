<?php

namespace RZP\Models\Merchant\AccessMap;

class PartnershipsAccessMapDTO
{

    private $merchant_id;
    private $entity_id;
    private $entity_type;
    private $entity_owner_id;
    private $fields;
    private $limit;
    private $offset;
    private $distinct;
    private $order_by;

    /**
     * @param $merchant_id
     * @param $entity_id
     * @param $entity_type
     * @param $limit
     * @param $offset
     * @param $distinct
     * @param $order_by
     */
    public function __construct($merchant_id = null,
                                $entity_id = null,
                                $entity_type = null,
                                $entity_owner_id = null,
                                $limit = null,
                                $fields=null,
                                $offset = null,
                                $distinct = null,
                                $order_by = null)
    {
        $this->merchant_id = $merchant_id;
        $this->entity_id = $entity_id;
        $this->entity_type = $entity_type;
        $this->entity_owner_id = $entity_owner_id;
        $this->fields=$fields;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->distinct = $distinct;
        $this->order_by = $order_by;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields): void
    {
        $this->fields = $fields;
    }

    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    public function setMerchantId($merchant_id): void
    {
        $this->merchant_id = $merchant_id;
    }


    public function getEntityId()
    {
        return $this->entity_id;
    }


    public function setEntityId($entity_id): void
    {
        $this->entity_id = $entity_id;
    }


    public function getEntityType()
    {
        return $this->entity_type;
    }


    public function setEntityType($entity_type): void
    {
        $this->entity_type = $entity_type;
    }


    public function getLimit()
    {
        return $this->limit;
    }


    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getEntityOwnerId()
    {
        return $this->entity_owner_id;
    }

    /**
     * @param mixed $entity_owner_id
     */
    public function setEntityOwnerId($entity_owner_id): void
    {
        $this->entity_owner_id = $entity_owner_id;
    }

    public function getDistinct()
    {
        return $this->distinct;
    }

    public function setDistinct($distinct): void
    {
        $this->distinct = $distinct;
    }

    public function getOrderBy()
    {
        return $this->order_by;
    }

    public function setOrderBy($order_by): void
    {
        $this->order_by = $order_by;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            if (!empty($value)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

}
