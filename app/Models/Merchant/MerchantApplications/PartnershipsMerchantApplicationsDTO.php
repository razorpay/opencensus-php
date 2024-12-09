<?php

namespace RZP\Models\Merchant\MerchantApplications;

class PartnershipsMerchantApplicationsDTO
{

    private $merchant_id;
    private $application_id;
    private $type;
    private $trashed;
    private $limit;
    private $offset;
    private $order_by;

    /**
     * @param $merchant_id
     * @param $application_id
     * @param $type
     * @param $trashed
     * @param $limit
     * @param $offset
     * @param $order_by
     */
    public function __construct(
        $merchant_id = null,
        $application_id = null,
        $type = null,
        $trashed = null,
        $limit = null,
        $offset = null,
        $order_by = null
    ) {
        $this->merchant_id = $merchant_id;
        $this->application_id = $application_id;
        $this->type = $type;
        $this->trashed = $trashed;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->order_by = $order_by;
    }


    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    public function setMerchantId($merchant_id): void
    {
        $this->merchant_id = $merchant_id;
    }

    public function getApplicationId()
    {
        return $this->application_id;
    }

    public function setApplicationId($application_id): void
    {
        $this->application_id = $application_id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getTrashed()
    {
        return $this->trashed;
    }

    public function setTrashed($trashed): void
    {
        $this->trashed = $trashed;
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
