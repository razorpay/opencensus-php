<?php

namespace Models\Manager;

use Models\Service;

class Payment extends Manager
{
    /**
     * Mappings of fields from Payment in API to Dashboard
     */
    protected static $api_dashboard_mappings = array(
        'id'            => 'id',
        'currency'      => 'currency',
        'amount'        => 'amount',
        'status'        => 'status',
        'amount_refunded' => 'amount_refunded',
        'refund_status' =>  'refund_status',
        'error_code'    => 'error_code',
        'error_description' => 'error_description',
        'created_at'    => 'created_at'
    );

    protected static $fetchRules = array(
        'id'            => 'alpha_dash|max:32',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric'
    );

    public static function mapKeys($response)
    {
        // @todo: this function can probably be improved.
        $data = array();
        foreach ($response['data'] as $obj)
        {
            $dataObj = [];
            foreach (static::$api_dashboard_mappings as $key => $value)
                $dataObj[$value] = $obj[$key];
            $data[] = $dataObj;
        }
        return array('data' => $data, 'count' => $response['count']);
    }
}