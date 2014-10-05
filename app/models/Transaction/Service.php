<?php

namespace Models\Transaction;

use Models\Base;

class Service extends Base\Service
{
    protected static $timeIntervals = array(
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
        $error = (new Transaction\Validator)->validateInput('process', $input);

        if (empty($error) === false)
        {
            return false;
        }

        $this->aggregate($input, $mode);

        // Only Payments analytics are stored
        if ($input['resource'] === "payment")
        {
            unset($input['resource']);

            foreach (static::$timeIntervals as $type => $interval)
            {
                $obj = Transaction\Entity::retrieveLastByType($input['merchant_id'], $type, $mode);
                $created_at = (int) $obj->create_at + $interval;

                if (($obj === null) or
                    ($created_at <= $input['updated_at']))
                {
                    $this->create($input, $type, $mode);
                }
                else
                {
                    $this->update($input, $obj);
                }
            }
        }

        return true;
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

    protected function aggregate($data, $mode)
    {
        $merchantDetails = Merchant\Entity::getAggregations($data, $mode);

        if ($merchantDetails === null)
        {
            Merchant\Entity::createAggregations($data, $mode);
        }
        else
        {
            Merchant\Entity::updateAggregations($data, $merchantDetails, $mode);
        }
    }

    protected function update($data, $obj)
    {
        $obj->updateAmount($data['amount']);
        $obj->updateCount(1);

        $obj->save();
    }

    public function getAggregations($merchantId, $mode)
    {
        return Merchant\Entity::getAggregations(array('merchant_id' => $merchantId), $mode);
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