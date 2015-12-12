<?php

namespace Models\Api;

use Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        $this->merchantId = \Auth::merchant()->id();
    }

    public function fetchEntity($id, $mode, $entity)
    {
        $error = (new Validator)->validateInput('fetch', array('id' => $id))->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $collection = [];
        
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api->$entity->fetch($id)->toArray();

            $collection = array(
                'count' => 1,
                'entity' => 'collection',
                'items' => array($data));

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $collection);
    }

    public function fetchCollection(array $input, $mode, $entity)
    {
        $method = 'fetchCollection' . $entity;
        if (method_exists($this, $method))
        {
            return $this->$method($input, $mode);
        }

        return $this->fetchEntityCollection($input, $mode, $entity);
    }

    protected function fetchEntityCollection(array $input, $mode, $entity)
    {
        $data = array();

        $error = (new Validator)->validateInput('fetch', $input)->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $collection = array();

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $collection = $this->api->$entity->all($input)->toArray();

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $collection);
    }

    protected function mapKeys(array & $collection)
    {
        $entity = $collection['entity'];
        $mapVar = $entity . 'Mappings';

        if (property_exists(get_class(), $mapVar) === false)
        {
            return;
        }

        foreach ($collection['items'] as $entity)
        {
            foreach (static::$$mapVar as $apiKey => $mapKey)
            {
                $entity[$mapKey] = $entity[$apiKey];

                unset($entity[$apiKey]);
            }
        }
    }

    public function fetchPaymentRefunds($id, $mode)
    {
        $data = array();

        $error = (new Validator)->validateInput('fetch', array('id' => $id), '')->messages();

        if (empty($error))
        {
            try
            {
                $this->setApiCredentials($this->merchantId, $mode);
                $collection = $this->api->payment
                                        ->fetch($id)
                                        ->refunds()
                                        ->all()
                                        ->toArray();

                $data = $collection['items'];
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function capturePayment($id, $amount, $mode)
    {
        $error = array();

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api->payment
                                ->fetch($id)
                                ->capture(array('amount' => $amount))
                                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
            return $error;
        }

        if (isset($data['error']) === true or isset($data['status']) === false or $data['status'] !== "captured")
            $error[] = "Capture Failed";

        return $error;
    }

    public function generateReportForMonth($month, $mode)
    {
        $data = array();
        $error = (new Validator)->validateInput('generateReport', compact('month'), '')
                                ->messages();

        if (empty($error))
        {
            try
            {
                $params = array(
                    'merchant_id' => $this->merchantId,
                    'month' => $month
                );
                $this->setApiCredentials($this->merchantId, $mode);
                $data = $this->api
                             ->transaction
                             ->generateReport($params)
                             ->toArray();
                
                //Sample endpoint for testing
                //$response = \Requests::get('http://jsonplaceholder.typicode.com/posts');
                //$data = json_decode($response->body, true);

                $file = $this->generateTransactionReportAsExcelFromDataForMonth($data, $month);
                
                return array($error, $file);
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
                return array($error, null);
            }
        }

        return array($error, null);
    }

    protected function generateTransactionReportAsExcelFromDataForMonth($data, $month)
    {
        $file = \Excel::create('transaction_report_' + $month, function($excel) use ($data, $month)
        {
            // Set the title
            $excel->setTitle("Transaction Report - $month");

            // Chain the setters
            $excel->setCreator('Razorpay')
                  ->setCompany('Razorpay');

            // Call them separately
            $excel->setDescription("Transaction report for the $month month");

            // Our first sheet
            $excel->sheet($month, function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });

        });

        return $file;
    }

    public function refundPayment($id, $amount, $mode)
    {
        $error = array();

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api->payment
                              ->fetch($id)
                              ->refund(array('amount' => $amount))
                              ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
            return $error;
        }

        if ($data['entity'] !== "refund" or $data['amount'] !== (int)$amount)
        {
            $error[] = "Refund Failed";
        }

        return $error;
    }
}