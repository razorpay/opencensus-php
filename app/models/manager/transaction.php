<?php

namespace Models\Manager;

use Models\Service;

class Transaction extends Manager
{
    /**
     * Mappings of fields from Transactions in API to Dashboard
     */
    protected static $api_dashboard_mappings = array(
        'id'            => 'transaction_id',
        'amount'        => 'amount',
        'status'        => 'status',
        'created_at'    => 'created_at',
        'updated_at'    => 'updated_at'
    );

    protected static $fetchRules = array(
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'status'        => 'in:failed,captured,capture_failed,auth,open,refunded,settlement_sent,settled'
    );

    public static function mapKeys($response)
    {
        // @todo: this function can probably be improved.
        $data = array();
        foreach ($response as $obj)
        {
            $dataObj = [];
            foreach(static::$api_dashboard_mappings as $key => $value)
                $dataObj[$value] = $obj->{$key};
            $data[] = $dataObj;
        }
        return $data;
    }
}