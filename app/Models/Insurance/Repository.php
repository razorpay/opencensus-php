<?php

namespace RZP\Models\Insurance;

use RZP\Base\ConnectionType;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'insurance';

    public function fetchInsurance(string $paymentId, string $orderId)
    {
        $insuranceRepo = $this->repo->insurance;

        $insuranceInsuredEntityIdColumn = $insuranceRepo->dbColumn(Entity::INSURED_ENTITY_ID);
        $insuranceInsuredEntityTypeColumn = $insuranceRepo->dbColumn(Entity::INSURED_ENTITY_TYPE);

        $q = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT));

        if (!empty($orderId))
        {
            $q->Where(function($query) use ($orderId, $insuranceInsuredEntityIdColumn, $insuranceInsuredEntityTypeColumn)
            {
                $query->where($insuranceInsuredEntityIdColumn, $orderId)
                    ->where($insuranceInsuredEntityTypeColumn, 'order');
            });
        }
        else
        {
            $q->where(function($query) use ($paymentId, $insuranceInsuredEntityIdColumn, $insuranceInsuredEntityTypeColumn)
            {
                $query->where($insuranceInsuredEntityIdColumn, $paymentId)
                    ->where($insuranceInsuredEntityTypeColumn, 'payment');
            });
        }

        return $q->first();
    }
}
