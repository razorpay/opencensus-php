<?php

namespace Models\Transaction;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $processRules = array(
        'merchant_id'   =>  'required',
        'amount'        =>  'required|integer',
        'created_at'    =>  'required|integer',
        'updated_at'    =>  'required|integer',
        'resource'      =>  'required|in:payment,refund,settlement',
        'method'        =>  'required_if:resource,payment|in:card,netbanking',
        'network'       =>  'required_if:method,card'
    );

    protected static $analyticsRules = array(
        'merchant_id'   =>  'required',
        'from'          =>  'integer',
        'to'            =>  'integer',
        'type'          =>  'in:day,week,month,year'
    );

    protected static $analyticsGenerators = array('type','from','to');

    public function validateAnalytics(& $input)
    {
        $error = $this->validateInput('analytics', $input)->messages();

        if (empty($error) === false)
        {
            return $error;
        }

        foreach (static::$analyticsGenerators as $generator)
        {
            $method = 'generate' . ucfirst($generator);

            $this->$method($input);
        }

        return [];
    }

    public function generateType(& $input)
    {
        if (isset($input['type']) === false)
        {
            $input['type'] = 'day';
        }
    }

    public function generateFrom(& $input)
    {
        if (isset($input['from']) === true)
        {
            return;
        }

        $type = (isset($input['type'])) ? $input['type'] : $this->data['type'];

        $time = time();

        switch ($type)
        {
            case 'day' :
                $time -= 31 * 24 * 60 * 60; // Last 1 day
                $time = strtotime(date('j F Y', $time));
                break;

            case 'week':
                $time -= 31 * 24 * 60 * 60; // Last 1 month
                $time = strtotime(date('o-\\WW', $time));
                break;

            case 'month':
                $time -= 6 * 31 * 24 * 60 * 60; // Last 6 months
                $time = strtotime(date('M Y', $time));
                break;

            case 'year':
                $time -= 5 * 365 * 24 * 60 * 60; // Last 5 years
                $time = strtotime('1 Jan ' . date('Y', $time));
                break;
        }
    }

    public function generateTo(& $input)
    {
        if (isset($input['to']) === false)
        {
            $input['to'] = time();
        }
    }
}