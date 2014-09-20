<?php

namespace Models\Manager;

use Models\Service;

class Transaction extends Manager
{
    /**
     * Mappings of fields from Payment in API to Dashboard
     */
    protected static $api_dashboard_mappings = array(
        'id'                =>  'id',
        'entity_id'         =>  'entity_id',    
        'entity_type'       =>  'entity_type',        
        'amount'            =>  'amount',    
        'debit'             =>  'debit',
        'credit'            =>  'credit',    
        'fee'               =>  'fee' 
    );

    protected static $fetchRules = array(
        'id'            => 'alpha_dash|max:32',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric'
    );

    protected static $processRules = array(
        'merchant_id'   =>  'required',
        'amount'        =>  'required|numeric|max:10000',
        'status'        =>  'required|in:captured,refunded,prefunded',
        'created_at'    =>  'required|numeric',
        'updated_at'    =>  'required|numeric'
    );

    protected static $analyticsRules = array(
        'merchant_id'   =>  'required',
        'from'          =>  'numeric',
        'to'            =>  'numeric',
        'type'          =>  'in:day,week,month,year'
    );

    protected static $analyticsGenerators = array('type','from','to');

    public function generateType($input)
    {
        if (!isset($input['type']))
            $this->setField('type','day');
    }

    public function generateFrom($input)
    {
        if (!isset($input['from']))
        {
            $type = (isset($input['type'])) ? $input['type'] : $this->data['type'];
            $time = time();
            switch($type)
            {
                case 'day' :
                    $time -= 31 * 24 * 60 * 60; //Last 1 month
                    $time = strtotime(date('j F Y', $time));
                    $this->setField('from', $time);
                    break;
                case 'week':
                    $time -= 31 * 24 * 60 * 60; //Last 1 month
                    $time = strtotime(date('o-\\WW', $time));
                    $this->setField('from', $time);
                    break;
                case 'month':
                    $time -= 5 * 31 * 24 * 60 * 60; //Last 5 months
                    $time = strtotime(date('M Y', $time));
                    $this->setField('from', $time);
                    break;
                case 'year':
                    $time -= 5 * 365 * 24 * 60 * 60; //Last 5 years
                    $time = strtotime('1 Jan ' . date('Y', $time));
                    $this->setField('from', $time);
                    break;
            }
        }
    }

    public function generateTo($input)
    {
        if (!isset($input['to']))
            $this->setField('to',time());
    }

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