<?php

namespace App\Api;

use Carbon\Carbon;
use App\Http\AppResponse;
use Auth;
use App\Base;

use App\Trace\TraceCode;
use Trace;

class Service extends Base\Service
{
    public function __construct()
    {
        $loggedInUser = Auth::user();

        if ($loggedInUser)
        {
            $this->merchant = $loggedInUser->currentMerchant();

            $this->merchantId = $this->merchant->id;
        }
        else
        {
            $this->merchantId = 'anonymous';
        }

        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
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

    public function fetchCollectionForAutocomplete($mode, $entity)
    {
        $error = null;
        $count = 100;
        $collection = [
            'entity' => 'collection',
            'count'  => 0,
            'items'  => [],
        ];

        for ($i = 0; $i < 5; $i++)
        {
            $input = [
                'skip' => $i * $count,
                'count' => $count
            ];

            list($error, $list) = $this->fetchEntityCollection($input, $mode, $entity);
            $collection['count'] = $collection['count'] + $list['count'];
            $collection['items'] = array_merge($collection['items'], $list['items']);

            if ($list['count'] < $count)
            {
                break;
            }
        }

        return [$error, $collection];
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

    /**
     * Sensitive function:
     * Uses admin auth on merchant dashboard
     *
     * Used to retrieve data for the Markerplace accounts list page
     * with API route - GET /merchants; filtered on field: parent_id
     * This is temp, until we roll out account APIs.
     * @todo Move to /account fetch, under private auth
     */
    public function fetchCollectionForMarketplaceAccounts($input)
    {
        if (isset($this->merchantId) === false)
        {
            return [['Internal error occurred'], null];
        }

        $error = (new Validator)->validateInput('fetch', $input)->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $collection = [];

        // Fetch merchants filtered by parent_id field
        $input['parent_id'] = $this->merchantId;

        try
        {
            $this->setApiCredentials();

            $collection = $this->api->merchant->all($input)->toArray();

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $collection];
    }

    public function capturePayment(string $id, string $mode, array $input)
    {
        $error = [];

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $data = $this->api->payment
                                ->fetch($id)
                                ->capture($input)
                                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
            return $error;
        }

        if (isset($data['error']) === true or isset($data['status']) === false or $data['status'] !== "captured")
        {
            $error[] = "Capture Failed";
        }

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

    /**
     * Generates a excel report for the given parameters
     * @param  string $mode  live|test
     * @param  array  $input query parameters to be passed to API
     */
    public function generateResourceReport($mode, $resource, $params = [])
    {
        $data = $error = [];

        // Increase the time limit for the excel generation
        set_time_limit(600);

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);
            $data = $this->api
                         ->transaction
                         ->generateEntityReportFile($resource, $params)
                         ->toArray();
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function generateTransactionBrokingReport($mode, $resource, $params = [])
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
        $error = $batchRefund = null;

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $batchRefund = $this->api
                                ->batch
                                ->uploadFile($mode, $this->merchantId, $input);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $batchRefund];
    }

    public function fetchMultipleBatches($mode, $input)
    {
        $error = $collection = null;

        try
        {
            $collection = [];

            $this->setApiCredentials($this->merchantId, $mode);

            $collection = $this->api
                               ->batch
                               ->all($input)
                               ->toArray();

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $collection];
    }

    public function fetchBatchById($mode, $id)
    {
        $error = $collection = null;

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $data = $this->api
                         ->batch
                         ->fetch($id)
                         ->toArray();

            $collection = [
                            'count' => 1,
                            'entity' => 'collection',
                            'items' => array($data),
                          ];

            $this->mapKeys($collection);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $collection];
    }

    public function downloadBatchFile($mode, $id)
    {
        $error = $downloadResponse = null;

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $downloadResponse = $this->api
                                     ->batch
                                     ->downloadFile($id)
                                     ->toArray();

        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $downloadResponse];
    }

    public function retryBatchFile($mode, $id)
    {
        $error = $batchRefund = null;

        try
        {
            $this->setApiCredentials($this->merchantId, $mode);

            $batchRefund = $this->api
                                ->batch
                                ->retryFile($id)
                                ->toArray();
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $batchRefund];
    }
}
