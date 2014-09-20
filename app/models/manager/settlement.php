<?php

namespace Models\Manager;

use Models\Service;

class Settlement extends Manager
{
    /**
     * Mappings of fields from Transactions in API to Dashboard
     */
    protected static $api_dashboard_mappings = array(
        'id'            => 'id',
        'amount'        => 'amount',
        'status'        => 'status'
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
            foreach(static::$api_dashboard_mappings as $key => $value)
                $dataObj[$value] = $obj[$key];
            $data[] = $dataObj;
        }
        return array('data' => $data, 'count' => $response['count']);
    }
}