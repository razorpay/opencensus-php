<?php

namespace Models\Api;

use Carbon\Carbon;
use Http\AppResponse;
use Auth;
use Models\Base;
use Trace;

class Service extends Base\Service
{
    // Corresponds to 15th November 2015 00:00
    const SB_CESS_START = 1447525800;

    public function __construct()
    {
        $loggedInUser = Auth::user()->user();

        if ($loggedInUser)
        {
            $this->merchantId = $loggedInUser->getCurrentMerchantId();
        }
        else
        {
            $this->merchantId = 'anonymous';
        }
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

    public function fetchOrderPayments($id, $mode)
    {
        $data = $error = [];
        $this->setApiCredentials($this->merchantId, $mode);
        try
        {
            $collection = $this->api->order
                ->setId($id)
                ->payments()
                ->toArray();

            $data = $collection['items'];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
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

    protected function generateTransactionReportAsExcel($data, $entity = "Transaction")
    {
        $file = \Excel::create("{$entity}_report", function($excel) use ($data, $entity)
        {
            // Set the title
            $excel->setTitle("$entity Report");

            // Chain the setters
            $excel->setCreator('Razorpay')->setCompany('Razorpay');

            // Call them separately
            $excel->setDescription("$entity Report Razorpay");

            // Our first sheet
            $excel->sheet('Export', function($sheet) use ($data)
            {
                foreach ($data as $index => &$row)
                {
                    if (is_array($row))
                    {
                        // This just converts array fields to JSON
                        // And escapes insecure values with quotes
                        $row = AppResponse::flatten($row);
                    }
                    else
                    {
                        // failsafe to make sure you don't call flatten
                        unset($data[$index]);
                    }

                }

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

    /**
     * Generates a excel report for the given parameters
     * @param  string $mode  live|test
     * @param  array  $input query parameters to be passed to API
     */
    public function generateResourceReport($mode, $resource, $params = [])
    {
        $data = $error = [];
        $file = null;

        // Increase the time limit for the excel generation
        set_time_limit(600);

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api
                         ->transaction
                         ->generateEntityReport($resource, $params)
                         ->toArray();

            $traceData = [
                'count' => count($data),
                'params'=> $params,
                'entity' => $resource
            ];

            // Put the first row in trace as well
            if (count($data) >= 1)
            {
                $traceData['first_row'] = $data[0];
                $file = $this->generateTransactionReportAsExcel($data, $resource);
            }
            else
            {
                $traceData['empty'] = true;
                $error = ['No data found for given range'];
            }

            Trace::debug('MISC_TRACE_CODE', $traceData);

            return array($error, $file);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
        catch(\Exception $exception)
        {
            $error[] = "Could not generate report. Please try again later";

            Trace::critical('ERROR_EXCEPTION', [
                'message'   => $exception->getMessage(),
                'code'      => $exception->getCode(),
                'stack'     => $exception->getTraceAsString(),
            ]);
        }

        return array($error, null);
    }

    public function getInvoiceReportData($mode, array $input)
    {
        set_time_limit(600);

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api
                         ->transaction
                         ->generateEntityReport('payment', $input)
                         ->toArray();

            $data = $this->summarizeReportData($input, $data);

            return [null, $data];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
        /*catch(\Exception $exception)
        {
            $error[] = "Could not generate report. Please try again later";
            $error[] = $exception->getMessage();

            Trace::critical('ERROR_EXCEPTION', [
                'message'   => $exception->getMessage(),
                'code'      => $exception->getCode(),
                'stack'     => $exception->getTraceAsString(),
            ]);
        }*/

        return array($error, null);
    }

    /**
     * Get the date ranges to be used in an invoice
     */
    protected function getDateRanges($year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Calcutta');

        return [
            'billingDate'    => $startDate->addMonth(),
            'startDate'      => $startDate,
            'endDate'        => $startDate->endOfMonth()
        ];
    }

    protected function sumField(array $data, $field)
    {
        $value = 0;
        array_walk($data, function($row) use ($field, &$value)
        {
            if (isset($row[$field]))
            {
                $value += $row[$field];
            }
        });

        return $value;
    }

    protected function summarizeReportData($params, $data)
    {
        foreach ($data as &$row)
        {
            $row['effective_fee'] = $row['fee'] - $row['service_tax'];
            $row['service_tax_only'] = $row['effective_fee'] * 0.14;

            /**
             * Note on the swach bharat cess. This was supposed to be flipped
             * after the 15th November 2015. However, we made the flip a couple
             * of days late, which is why we are re-calculating here again.
             */
            $timestamp = Carbon::createFromFormat('d/m/y h:i:s',
                $row['created_at'], 'Asia/Calcutta')->getTimestamp();
            if ($timestamp >= self::SB_CESS_START)
            {
                $row['sb_cess']          = $row['effective_fee'] * 0.005;
            }
        }

        return [
            'data'  =>  $data,
            'dates' =>  $this->getDateRanges($params['year'], $params['month']),
            'effective_fee' => $this->sumField($data, 'effective_fee'),
            'sb_cess'       => $this->sumField($data, 'sb_cess'),
            'service_tax_only' => $this->sumField($data, 'service_tax_only'),
        ];
    }
}
