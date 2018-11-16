<?php

namespace RZP\Dashboard;

use RZP\Models\Payment\Entity as PaymentEntity;

class Payment extends Dashboard
{
    protected static $resource = 'payment';

    /**
     * Any changes to this array
     * has to be reflected in dashboard also.
     * Dashboard: Manager\Payment
     *
     * @var array
     */
    protected static $fields = array(
        PaymentEntity::ID,
        PaymentEntity::MERCHANT_ID,
        PaymentEntity::METHOD,
        PaymentEntity::AMOUNT,
        PaymentEntity::CREATED_AT,
        PaymentEntity::UPDATED_AT,
        'network');
}
