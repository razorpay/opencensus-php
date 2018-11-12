<?php

namespace RZP\Gateway\Amex;

use RZP\Gateway\AxisMigs;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'amex';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('amex', '=', '1');
    }

    /**
     * Used in Payment Reconciliate for fetching
     * payment by given gateway vpc_TransacationNo
     * for authorize Action
     * @param string $referenceNumber
     * @return Entity
     */
    public function findPaymentForGateway(string $referenceNumber)
    {
        return $this->newQuery()
            ->where(Entity::VPC_TRANSACTION_NUMBER, '=', $referenceNumber)
            ->where(Entity::ACTION, '=' ,'authorize')
            ->firstOrFail();
    }
}
