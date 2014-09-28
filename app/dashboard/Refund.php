<?php

namespace Dashboard;

use Models\Payment\Refund\Entity as RefundEntity;

class Refund extends Dashboard
{
    protected static $resource = 'refund';

    /**
     * Any changes to this array
     * has to be reflected in dashboard also.
     * Dashboard: Manager\Payment
     *
     * @var array
     */
    protected static $fields = array(
        RefundEntity::MERCHANT_ID,
        RefundEntity::AMOUNT,
        RefundEntity::CREATED_AT,
        RefundEntity::UPDATED_AT
    );
}