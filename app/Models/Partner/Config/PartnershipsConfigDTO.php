<?php

namespace RZP\Models\Partner\Config;

class PartnershipsConfigDTO
{

    private $entity_id;
    private $entity_type;
    private $origin_id;
    private $origin_type;
    private $limit;
    private $offset;
    private $order_by;

    /**
     * @param $entity_id
     * @param $entity_type
     * @param $origin_id
     * @param $origin_type
     * @param $limit
     * @param $offset
     * @param $order_by
     */
    public function __construct( $entity_id = null,
                                 $entity_type = null,
                                 $origin_id = null,
                                 $origin_type = null,
                                 $limit=null, $offset=null, $order_by=null)
    {
        $this->entity_id = $entity_id;
        $this->entity_type = $entity_type;
        $this->origin_id = $origin_id;
        $this->origin_type = $origin_type;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->order_by = $order_by;
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

    public function getOriginId()
    {
        return $this->origin_id;
    }

    public function setOriginId($origin_id): void
    {
        $this->origin_id = $origin_id;
    }

    public function getOriginType()
    {
        return $this->origin_type;
    }

    public function setOriginType($origin_type): void
    {
        $this->origin_type = $origin_type;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit): void
    {
        $this->limit = $limit;
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
