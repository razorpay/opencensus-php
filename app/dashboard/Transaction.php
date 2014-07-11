<?php

namespace Dashboard;

use Models\Transaction;

class Transaction extends Dashboard
{
    protected static $resource = 'transactions';

    /**
     * Any changes to this array
     * has to be reflected in dashboard also.
     * Dashboard: Manager\Transaction
     *
     * @var array
     */
    protected static $fields = array(
        Transaction\Entity::MERCHANT_ID,
        Transaction\Entity::AMOUNT,
        Transaction\Entity::STATUS,
        Transaction\Entity::CREATED_AT,
        Transaction\Entity::UPDATED_AT'
    );
}