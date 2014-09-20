<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Transaction extends Service
{
    protected static $timeIntervals = array(
        'day'   =>  86400, // 24 * 60 * 60
        'week'  =>  604800, // 7 * 24 * 60 * 60
        'month' =>  2678400, // 31 * 24 * 60 * 60
        'year'  =>  31536000 // 365 * 24 * 60 * 60
    );

    public function fetchListFromApi(array $input, $mode)
    {
        $data = array();

        list($error,$options) = Manager\Transaction::createValidate($input, 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            $this->setApiCredentials($merchant_id, $mode);

            $response = $this->api->ledger->all($options)->toArray();

            $data = Manager\Transaction::mapKeys($response);
        }

        return array($error, $data);
    }

    public function fetchFromApi($id, $mode)
    {
        $data = array();

        list($error, $options) = Manager\Transaction::createValidate(['id' => $id], 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            $this->setApiCredentials($merchant_id, $mode);

            $id = $options['id'];
            try
            {
                $data = $this->api->ledger->fetch($id)->toArray();

                $data = array('count' => 1, 'data' => array($data));
            }
            catch(\Exception $e)
            {
                $error[] = 'Transaction not found.';
            }
        }

        return array($error, $data);
    }

    /**
     * Processes incoming transaction records to generate analytics.
     * @param  array  $input transaction array
     * @return bool          status
     */
    public function process(array $input, $mode)
    {
        list($error, $data) = Manager\Transaction::createValidate($input, 'process')->getData();

        if (empty($error))
        {
            switch ($data['status'])
            {
                case 'captured':

                    $this->aggregate($data, $mode);

                    foreach (static::$timeIntervals as $type => $interval)
                    {
                        $obj = DAL\Transaction::retrieveLastByType($data['merchant_id'], $type, $mode);

                        if (NULL === $obj || strtotime($obj->created_at) < ($data['updated_at'] - $interval))
                            $this->create($data, $type, $mode);
                        else
                            $this->update($data, $obj);
                    }

                    return true;
                    break;

                default:
                    return false;
                    break;
            }
        }
        else
            return false;
    }

    protected function create($data, $type, $mode)
    {
        switch($type)
        {
            case 'day':
                $data['created_at'] = strtotime(date('j F Y', $data['updated_at']));
                break;
            case 'week':
                $data['created_at'] = strtotime(date('o-\\WW', $data['updated_at']));
                break;
            case 'month':
                $data['created_at'] = strtotime(date('M Y', $data['updated_at']));
                break;
            case 'year':
                $data['created_at'] = strtotime("1 Jan " . date('Y', $data['updated_at']));
                break;
        }
        $data['type'] = $type;
        $data['mode'] = $mode;
        DAL\Transaction::createOrFail($data);
    }

    protected function aggregate($data, $mode)
    {
        $merchant_details = DAL\Merchant::getAggregations($data, $mode);
        if (NULL === $merchant_details)
        {
            DAL\Merchant::createAggregations($data, $mode);
        }
        else
        {
            DAL\Merchant::updateAggregations($data, $merchant_details, $mode);
        }
    }

    protected function update($data, $obj)
    {
        $obj->updateAmount($data['amount']);
        $obj->updateCount(1);

        $obj->save();
    }

    public function getAggregations($merchant_id, $mode)
    {
        $data = DAL\Merchant::getAggregations(array('merchant_id' => $merchant_id), $mode);
        return $data;
    }

    public function getAnalytics($input, $mode)
    {
        list($error, $input) = Manager\Transaction::createValidate($input, 'analytics')->getData();

        if (empty($error))
        {
            $data = DAL\Transaction::where('merchant_id','=',$input['merchant_id'])
                            ->where('type','=',$input['type'])
                            ->where('created_at','>=',$input['from'])
                            ->where('created_at','<=',$input['to'])
                            ->where('mode', '=', $mode)
                            ->get();

            $data = $this->fillMissing($input, $data);

            return $data;
        }
        else
        {
            return [];
        }
    }

    //@todo: see if this function can be improved.
    protected function fillMissing($input, $array)
    {
        $data = [];
        for ($i = $input['from']; $i <= $input['to']; $i = strtotime('+1 ' . $input['type'], $i) )
        {
            $flag = false;

            switch($input['type'])
            {
                case 'day':
                    $i = strtotime(date('j F Y', $i));
                    break;
                case 'week':
                    $i = strtotime(date('o-\\WW', $i));
                    break;
                case 'month':
                    $i = strtotime(date('M Y', $i));
                    break;
                case 'year':
                    $i = strtotime('1 Jan ' . date('Y', $i));
                    break;
            }

            foreach ($array as $obj)
            {
                if ((strtotime($obj->created_at)) == $i)
                {
                    $data[] = $obj->toArray();
                    $flag = true;
                    break;
                }
            }

            if ($flag == false)
                $data[] = ['amount' => '0', 'count' => '0', 'created_at' => "$i"];
        }
        return $data;
    }
}