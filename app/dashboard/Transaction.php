<?php

namespace Dashboard;

use Models\Transaction\Entity as TransactionEntity;

class Transaction extends Dashboard
{
    protected static $resource = 'transactions';

    //Any changes to this array
    //has to be reflected in dashboard also.
    //Dashboard: Manager\Transaction
    protected static $fields = array(
        TransactionEntity::MERCHANT_ID,
        TransactionEntity::AMOUNT,
        TransactionEntity::STATUS,
        'updated_at',
        'created_at'
    );
}