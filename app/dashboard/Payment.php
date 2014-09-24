<?php

namespace Dashboard;

use Models\Payment\Entity as PaymentEntity;

class Payment extends Dashboard
{
    protected static $resource = 'payments';

    /**
     * Any changes to this array
     * has to be reflected in dashboard also.
     * Dashboard: Manager\Payment
     *
     * @var array
     */
    protected static $fields = array(
        PaymentEntity::MERCHANT_ID,
        PaymentEntity::AMOUNT,
        PaymentEntity::STATUS,
        PaymentEntity::CREATED_AT,
        PaymentEntity::UPDATED_AT);
}