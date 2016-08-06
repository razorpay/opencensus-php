<?php

namespace App\Transaction;

use App\Base;
use App\Transaction;
use App\Merchant;
use App\MerchantDetails;

class Service extends Base\Service
{
    public static $timeIntervals = array(
        'day'   =>  86400, // 24 * 60 * 60
        'week'  =>  604800, // 7 * 24 * 60 * 60
        'month' =>  2678400, // 31 * 24 * 60 * 60
        'year'  =>  31536000 // 365 * 24 * 60 * 60
    );

    /**
     * Processes incoming transaction records to generate analytics.
     * @param  array  $input transaction array
     * @return bool          status
     */
    public function process(array $input, $mode)
    {
        $error = (new Transaction\Validator)->validateInput('process', $input)->messages();

        if (empty($error) === false)
        {
            return $error;
        }

        $this->aggregate($input, $mode);

        // Only Payments analytics are stored
        if ($input['resource'] === "payment")
        {
            $this->aggregatePayment($input, $mode);

            foreach (static::$timeIntervals as $type => $interval)
            {
                $obj = Transaction\Entity::retrieveLastByType($input['merchant_id'], $type, $mode);

                if (($obj === null) or
                    ((int) $obj->created_at->timestamp + $interval <= $input['updated_at']))
                {
                    $this->create($input, $type, $mode);
                }
                else
                {
                    $this->update($input, $obj);
                }
            }
        }

        return array();
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

        Transaction\Entity::createOrFail($data);
    }

    protected function createAggregate($mid, $data, $type, $mode)
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
        $data['merchant_id'] = $mid;

        Transaction\Entity::createOrFail($data);
    }

    protected function aggregate($data, $mode)
    {
        $merchantDetails = Merchant\Entity::getAggregations($data, $mode);

        if ($merchantDetails === null)
        {
            $merchantDetails = Merchant\Entity::createAggregations($data, $mode);
        }

        Merchant\Entity::updateAggregations($data, $merchantDetails, $mode);

    }

    protected function aggregatePayment($data, $mode)
    {
        $merchantDetails = Merchant\Entity::getPaymentAggregations($data, $mode);

        if ($merchantDetails === null)
        {
            $merchantDetails = Merchant\Entity::createPaymentAggregations($data, $mode);
        }

        Merchant\Entity::updatePaymentAggregations($data, $merchantDetails, $mode);
    }

    protected function update($data, $obj)
    {
        $obj->updateAmount($data['amount']);
        $obj->updateCount(1);

        $obj->save();
    }

    protected function updateAggregate($data, $obj)
    {
        $obj->forceUpdateAmount($data['amount']);
        $obj->forceUpdateCount($data['count']);

        $obj->save();
    }

    public function getAggregations($merchantId, $mode)
    {
        $resources = array('payment', 'refund', 'settlement');

        $response = array();

        foreach($resources as $resource)
        {
            $data = array('merchant_id' => $merchantId, 'resource' => $resource);

            $response[$resource] = Merchant\Entity::getAggregations($data, $mode);
        }

        return $response;
    }

    public function getAllAggregations($mode, $resource, $sort)
    {
        return Merchant\Entity::getAllAggregations($mode, $resource, $sort);
    }

    public function getPaymentAggregations($merchantId, $mode)
    {
        $data = array('merchant_id' => $merchantId);

        $response = Merchant\Entity::getPaymentAggregations($data, $mode);

        return $response;
    }

    public function getAnalytics($input, $mode)
    {
        $error = (new Transaction\Validator)->validateAnalytics($input);

        if (empty($error) === false)
        {
            return [];
        }

        $data = Transaction\Entity::where('merchant_id','=',$input['merchant_id'])
                        ->where('type','=',$input['type'])
                        ->where('created_at','>=',$input['from'])
                        ->where('created_at','<=',$input['to'])
                        ->where('mode', '=', $mode)
                        ->get();

        $data = $this->fillMissing($input, $data);

        return $data;
    }

    // @todo: see if this function can be improved.
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
                if ((int)($obj->created_at->timestamp) == $i)
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

    public function processDayAggregations(array $input, $mode)
    {
        $inputByMerchant = [];

        foreach ($input as $value) {
            $this->filterInput($value, $inputByMerchant);
        }

        foreach ($inputByMerchant as $key => $value) {

            $created_at = strtotime(date('j F Y', $value['updated_at']));

            $type = 'day';

            $this->createOrUpdate($key, $value, $type, $created_at, $mode);
        }

        return array();
    }

    public function processWeekAggregations(array $input, $mode)
    {
        $inputByMerchant = [];

        foreach ($input as $value) {

            $this->filterInput($value, $inputByMerchant);
        }

        foreach ($inputByMerchant as $key => $value) {

            $created_at = strtotime(date('o-\\WW', $value['updated_at']));

            $type = 'week';

            $this->createOrUpdate($key, $value, $type, $created_at, $mode);
        }

        return array();
    }

    public function processMonthAggregations(array $input, $mode)
    {
        $inputByMerchant = [];

        foreach ($input as $value) {

            $this->filterInput($value, $inputByMerchant);
        }

        foreach ($inputByMerchant as $key => $value) {

            $created_at = strtotime(date('M Y', $value['updated_at']));
            sd($created_at);

            $type = 'month';

            $this->createOrUpdate($key, $value, $type, $created_at, $mode);
        }

        return array();
    }

    public function processYearAggregations(array $input, $mode)
    {
        $inputByMerchant = [];

        foreach ($input as $value) {

            $this->filterInput($value, $inputByMerchant);
        }

        foreach ($inputByMerchant as $key => $value) {

            $created_at = strtotime('1 Jan ' . date('Y', $value['updated_at']));

            $type = 'year';

            $this->createOrUpdate($key, $value, $type, $created_at, $mode);
        }

        return array();
    }

    protected function filterInput($value, &$inputByMerchant)
    {
        if ($value['captured_at'] === NULL)
        {
            return array();
        }

        if (!isset($inputByMerchant[$value['merchant_id']]['count']))
        {
            $inputByMerchant[$value['merchant_id']]['count'] = 1;
        }
        else
        {
            $inputByMerchant[$value['merchant_id']]['count'] += 1;
        }

        if (!isset($inputByMerchant[$value['merchant_id']]['amount']))
        {
            $inputByMerchant[$value['merchant_id']]['amount'] = $value['amount'];
        }
        else
        {
            $inputByMerchant[$value['merchant_id']]['amount'] += $value['amount'];
        }

        if (!isset($inputByMerchant[$value['merchant_id']]['updated_at']))
        {
            $inputByMerchant[$value['merchant_id']]['updated_at'] = time();
        }
    }

    protected function createOrUpdate($key, $inputByMerchant, $type, $created_at, $mode)
    {
        $obj = Transaction\Entity::retrieveByTypeAndCreatedAt($key, $type, $created_at, $mode);

        if (($obj === null) or
            ((int) $obj->created_at->timestamp + self::$timeIntervals[$type] <= $inputByMerchant['updated_at']))
        {
            $this->createAggregate($key, $inputByMerchant, $type, $mode);
        }
        else
        {
            $this->updateAggregate($inputByMerchant, $obj);
        }
    }
}
