<?php

namespace Dashboard;

use Models\Settlement\Entity as SettlementEntity;

class Settlement extends Dashboard
{
    protected static $resource = 'settlement';

    /**
     * Any changes to this array
     * has to be reflected in dashboard also.
     * Dashboard: Manager\Payment
     *
     * @var array
     */
    protected static $fields = array(
        SettlementEntity::MERCHANT_ID,
        SettlementEntity::AMOUNT,
        SettlementEntity::CREATED_AT,
        SettlementEntity::UPDATED_AT);
}