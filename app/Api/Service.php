<?php

namespace App\Api;

use Carbon\Carbon;
use App\Http\AppResponse;
use Auth;
use App\Base;
use Trace;

class Service extends Base\Service
{

    public function __construct()
    {
        $loggedInUser = Auth::user();

        if ($loggedInUser)
        {
            $this->merchantId = $loggedInUser->getCurrentMerchantId();
            $this->merchant   = $loggedInUser->currentMerchant;
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

    public function fetchCardDetails($paymentId, $mode)
    {
        $data = $error = [];
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api->payment->fetch($paymentId)->card()->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        return [$error, $data];
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

    public function generateTransactionReportAsExcel($data, $entity = "Transaction")
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

    public function refundPayment($id, $input, $mode)
    {
        $error = array();

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api->payment
                              ->fetch($id)
                              ->refund($input)
                              ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
            return $error;
        }

        if ($data['entity'] !== "refund" or $data['amount'] !== (int) $input['amount'])
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
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api
                         ->transaction
                         ->getInvoiceData($input)
                         ->toArray();

            $data['dates']      = $this->getDateRanges($input['year'], $input['month']);
            $data['merchant']   = $this->merchant;
            $data['invoice_id'] = $this->getInvoiceId($input['year'], $input['month']);

            return [null, $data];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }

        return [$error, null];
    }

    /**
     * Generic method to get an entity
     */
    public function getEntity($mode, $entity)
    {
      $this->setApiCredentials($this->merchantId, $mode);

      return $this->api->$entity;
    }

    /**
     * Get the date ranges to be used in an invoice
     */
    protected function getDateRanges($year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Calcutta');

        return [
            'startDate'      => $this->getDate($year, $month)->format('d/m/y'),
            'billingDate'    => $this->getDate($year, $month)->endOfMonth()->format('d/m/y'),
            'endDate'        => $this->getDate($year, $month)->endOfMonth()->format('d/m/y')
        ];
    }

    protected function getDate($year,$month)
    {
        return Carbon::createFromDate($year, $month, 1, 'Asia/Calcutta');
    }

    protected function getInvoiceId($year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Calcutta');
        return $this->merchant->id . '/' . $startDate->addMonth()->format('m/y');
    }

    public function uploadBatchFile($mode, $input)
    {
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $batchRefund = $this->api
                                ->batch
                                ->uploadBatchFile($mode, $this->merchantId, $input);

            return [null, $batchRefund];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
    }

    public function fetchMultipleBatches($mode, $input)
    {
        try
        {
            $collection = array();

            $this->setApiCredentials($this->merchantId, $mode);

            $collection = $this->api
                               ->batch
                               ->fetchMultipleBatches($input)
                               ->toArray();

            $this->mapKeys($collection);

            return [null, $collection];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
    }

    public function fetchBatchById($mode, $id)
    {
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $data = $this->api
                         ->batch
                         ->fetchBatchById($id)
                         ->toArray();

            $collection = array(
                            'count' => 1,
                            'entity' => 'collection',
                            'items' => array($data));

            $this->mapKeys($collection);

            return [null, $collection];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
    }

    public function downloadBatchFile($mode, $id)
    {
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $downloadResponse = $this->api
                                     ->batch
                                     ->downloadBatchFile($id)
                                     ->toArray();

            return [null, $downloadResponse];

        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }

    }

    public function retryBatchFile($mode, $id)
    {
        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $batchRefund = $this->api
                                ->batch
                                ->retryBatchFile($id)
                                ->toArray();

            return [null, $batchRefund];
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();

            return array($error, null);
        }
    }
}
