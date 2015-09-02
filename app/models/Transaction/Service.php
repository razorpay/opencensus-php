<?php

namespace Models\Transaction;

use Models\Base;
use Models\Transaction;
use Models\Merchant;
use Models\MerchantDetails;
use Mail;

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
        $error = (new Transaction\Validator)->validateInput('process', $input)->messages();

        if (empty($error) === false)
        {
            return $error;
        }

        $this->aggregate($input, $mode);

        if($input['resource'] === "refund" and $mode === 'live')
        {
            $this->slackPost('New Refund',
                $this->slackData($input),
                '#transactions', '@channel'
            );
        }

        // Only Payments analytics are stored
        if ($input['resource'] === "payment")
        {
            if($mode === 'live')
            {
                $this->slackPost('New Payment',
                    $this->slackData($input),
                    '#transactions', null
                );

                $this->sendMail($input);
            }

            $this->aggregatePayment($input, $mode);

            foreach (static::$timeIntervals as $type => $interval)
            {
                $obj = Transaction\Entity::retrieveLastByType($input['merchant_id'], $type, $mode);

                if (($obj === null) or
                    ((int) $obj->create_at + $interval <= $input['updated_at']))
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

    protected function slackData($input)
    {
        $merchant = MerchantDetails\Entity::findorfail($input['merchant_id']);

        $keysToDrop = ['created_at', 'updated_at', 'merchant_id'];
        foreach ($keysToDrop as $key)
        {
            unset($input[$key]);
        }

        $input['merchant'] = $merchant->business_dba;
        $input['website'] = $merchant->business_website;
        return $input;
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
                if ((int)($obj->created_at) == $i)
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

    protected function sendMail($input)
    {
        if($_ENV['CONTEXT'] === 'production')
        {
            $merchant = Merchant\Entity::findorfail($input['merchant_id']);

            $input['email'] = $merchant->email;
            $input['name'] = $merchant->name;

            $input['amount'] = "INR ".number_format($input['amount']/100, 2);

            Mail::send('emails.payment', compact('input'), function($m) use($input)
            {
                $m->to($input['email'], $input['name'])
                  ->subject('Razorpay - New Payment');
            });
        }
    }
}
