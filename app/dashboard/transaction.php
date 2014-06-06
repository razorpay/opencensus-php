<?php

namespace Dashboard;

class Transaction extends Dashboard
{
    protected static $resource = 'transactions';

    /**
     * Mappings from API to Dashboard
     */
    protected static $fields = array(
        'merchant_id'   => 'merchant_id',
        'amount'        => 'amount',
        'status'        => 'status',
        'updated_at'    => 'created_at'
    );
}