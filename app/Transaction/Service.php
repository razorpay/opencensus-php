<?php

namespace App\Transaction;

use App\Base;
use App\Transaction;
use App\Merchant;
use Carbon\Carbon;
use App\MerchantDetails;
use DB;
use App\Trace\TraceCode;

class Service extends Base\Service
{
    const TIME_INTERVALS = [
        'day'   =>  86400, // 24 * 60 * 60
        'week'  =>  604800, // 7 * 24 * 60 * 60
        'month' =>  2678400, // 31 * 24 * 60 * 60
        'year'  =>  31536000 // 365 * 24 * 60 * 60
    ];

    public function __construct()
    {
        $app = \App::getFacadeRoot();
        $this->trace = $app['trace'];
    }

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

            foreach (self::TIME_INTERVALS as $type => $interval)
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
        $data['created_at'] = $this->getCreatedAtFromInputAndType($data['updated_at'], $type);
        $data['type'] = $type;
        $data['mode'] = $mode;

        Transaction\Entity::createOrFail($data);
    }

    protected function createAggregate($mid, $data, $type, $mode, $createdAt)
    {
        $data['created_at'] = $createdAt;
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

            $j = $this->getCreatedAtFromInputAndType($i, $input['type']);

            foreach ($array as $obj)
            {
                if ((int)($obj->created_at->timestamp) == $j)
                {
                    $data[] = $obj->toArray();
                    $flag = true;
                    break;
                }
            }

            if ($flag == false)
            {
                $data[] = ['amount' => '0', 'count' => '0', 'created_at' => "$j"];
            }
        }
        return $data;
    }

    public function processDayAggregations(array $paymentsByMerchant, $mode)
    {
        $error = [];
        try
        {
            $inputByMerchant = [];
            foreach ($paymentsByMerchant as $merchantId => $paymentByMerchant)
            {
                $inputByMerchant[$merchantId]['count'] = count($paymentByMerchant);
                $inputByMerchant[$merchantId]['amount'] = 0;
                $inputByMerchant[$merchantId]['created_at'] = $paymentByMerchant[0]['created_at'];

                foreach ($paymentByMerchant as $value)
                {
                    $inputByMerchant[$merchantId]['amount'] += $value['amount'];
                }
            }

            foreach ($inputByMerchant as $merchantId => $value)
            {
                $date = date('j F Y', $value['created_at']);

                $createdAt = Carbon::parse($date, 'Asia/Kolkata')->timestamp;

                $type = 'day';

                $this->createOrUpdate($merchantId, $value, $type, $createdAt, $mode);
            }
        }
        catch (\Exception $e)
        {
            $error[] = $e->getMessage();
        }


        return array($error, null);
    }

    protected function createOrUpdate($merchantId, $inputByMerchant, $type, $createdAt, $mode)
    {
        $this->trace->info(TraceCode::MISC_TRACE_CODE, [$merchantId, $inputByMerchant]);

        $obj = Transaction\Entity::retrieveByTypeAndCreatedAt($merchantId, $type, $createdAt, $mode);

        if ($obj === null)
        {
            $this->createAggregate($merchantId, $inputByMerchant, $type, $mode, $createdAt);
        }
        else
        {
            $this->updateAggregate($inputByMerchant, $obj);
        }
    }

    public function updateTypeAggregations($data, $createdAt, $mode, $type)
    {
        $error = [];
        try
        {
            foreach ($data as $merchant_aggregate)
            {
                $merchantId = $merchant_aggregate->merchant_id;
                $input = [];
                $input['created_at'] = $merchant_aggregate->created_at;
                $input['updated_at'] = time();
                $input['amount'] = $merchant_aggregate->amount;
                $input['count'] = $merchant_aggregate->count;
                $input['merchant_id'] = $merchantId;
                $this->createOrUpdate($merchantId, $input, $type, $createdAt, $mode);
            }
        }
        catch(\Exception $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, null);
    }

    public function getCreatedAtFromInputAndType($date, $type)
    {
        switch($type)
        {
            case 'day':
                $createdAt = Carbon::parse(date('j F Y', $date), 'Asia/Kolkata')->timestamp;//strtotime(date('j F Y', $date)); //2 January 2011
                break;
            case 'week':
                $createdAt = Carbon::parse(date('o-\\WW', $date), 'Asia/Kolkata')->timestamp; //2011-W52
                break;
            case 'month':
                $createdAt = Carbon::parse(date('M Y', $date), 'Asia/Kolkata')->timestamp; //Jan 2011
                break;
            case 'year':
                $createdAt = Carbon::parse("1 Jan " . date('Y', $date), 'Asia/Kolkata')->timestamp; //1 Jan 2011
                break;
        }

        return $createdAt;
    }

    /**
    * This gets the transactions of the days for a week, of the weeks for a month and so on.
    * These transactions are then aggregated upon for the week, month and so on.
    */
    public function getTimelyTransactionsForTheType($createdAt, $mode, $type)
    {
        $searchType = '';
        switch ($type)
        {
            case 'week':
                $searchType = 'day';
                break;

            case 'month':
                $searchType = 'week';
                break;

            case 'year':
                $searchType = 'month';
                break;

            default:
                break;
        }
        $endDate = $createdAt + self::TIME_INTERVALS[$type];

        $data = Transaction\Entity::select('merchant_id', DB::raw('sum(count) as count'), DB::raw('sum(amount) as amount'))
            ->where('type','=',$searchType)
            ->where('created_at','>=',$createdAt)
            ->where('created_at','<',$endDate)
            ->where('mode', '=', $mode)
            ->groupBy('merchant_id')
            ->get();

        return $data;
    }
}
