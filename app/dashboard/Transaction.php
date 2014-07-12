<?php

namespace Dashboard;

use Models\Transaction\Entity as TransactionEntity;

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
        TransactionEntity::MERCHANT_ID,
        TransactionEntity::AMOUNT,
        TransactionEntity::STATUS,
        TransactionEntity::CREATED_AT,
        TransactionEntity::UPDATED_AT);
}