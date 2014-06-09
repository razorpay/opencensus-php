<?php

namespace Dashboard;

class Transaction extends Dashboard
{
    protected static $resource = 'transactions';

    protected static $fields = array(
        'merchant_id',
        'amount',
        'status',
        'updated_at',
        'created_at'
    );
}