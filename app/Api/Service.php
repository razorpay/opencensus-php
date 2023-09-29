<?php

namespace App\Api;

use App\Admin\RawApiRequest;
use Auth;
use Trace;
use Request;
use App\Base;
use Carbon\Carbon;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\Generic;

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

    public function fetchCollectionForAutocomplete(
        string $mode,
        string $entity,
        array $params = [])
    {
        $error = null;
        $count = 100;

        $collection = [
            'entity' => 'collection',
            'count'  => 0,
            'items'  => [],
        ];

        if ($entity === 'customer')
        {
            $path = 'customers';
        }
        else if ($entity === 'item')
        {
            $path = 'items';
        }

        // Only proxy auth
        $request = new \App\Admin\ApiRequestAny([
            'client_type'   => 'merchant',
            'mode'          => $mode
        ]);

        for ($i = 0; $i < 10; $i++)
        {
            $offsets = [
                'skip'  => $i * $count,
                'count' => $count,
            ];

            $query_params = array_merge($params, $offsets);

            list($error, $list) = $request->send($path . '?' . http_build_query($query_params), 'GET');

            if (!empty($error))
            {
                break;
            }

            $collection['count'] = $collection['count'] + $list['count'];
            $collection['items'] = array_merge($collection['items'], $list['items']);

            if ($list['count'] < $count)
            {
                break;
            }
        }

        return [$error, $collection];
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
            $merchantId = $this->merchantId;

            if (isset($params['merchant_id']) === true)
            {
                $merchantId = $params['merchant_id'];
                unset($params['merchant_id']);
            }

            $this->setApiCredentials($merchantId, $mode);

            if (isset($params['account_id']) === true)
            {
                $this->setAccountCredentials($params['account_id']);

                unset ($params['account_id']);
            }

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
            $merchantId = $this->merchantId;
            if (isset($params['merchant_id']))
            {
                $merchantId = $params['merchant_id'];
                unset($params['merchant_id']);
            }

            $this->setApiCredentials($merchantId, $mode);

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

    public function getInvoiceReportData($mode, array $input, $merchantId)
    {
        $merchantId = $merchantId ?? $this->merchantId;

        try
        {
            $this->setApiCredentials($merchantId, $mode);

            $data = $this->api
                         ->transaction
                         ->getInvoiceData($input)
                         ->toArray();

            $data['dates']       = $this->getDateRanges($input['year'], $input['month']);
            $data['merchant_id'] = $merchantId;
            $data['invoice_id']  = $this->getInvoiceId($input['year'], $input['month'], $merchantId);

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

    protected function getInvoiceId($year, $month, $merchantId)
    {
        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Calcutta');
        return $merchantId . '/' . $startDate->addMonth()->format('m/y');
    }
    
    public function handleMagicAnalyticsOAuthCallbackURL(array $input)
    {
        $request = new \App\Admin\ApiRequestAny([
            'client_type' => 'merchant',
        ]);

        list($error, $data) = $request->send($input['path'] . '?' . http_build_query($input['query_params']), 'GET');

        if (!empty($error))
        {
            Trace::info(TraceCode::MAGIC_ANALYTICS_OAUTH_CALLBACK_FAILED, [
                'error' => $error,
            ]);    
        }
        
        $targetUrl = '';
        
        if(empty($data) === false && empty($data['target_url']) === false)
        {
            $targetUrl = $data['target_url'];
        }
        
        if ($targetUrl === '')
        {
            $targetUrl = Request::root() . '/app/magic/settings/analytics-settings?platform=google-ads&callback_error=failed_to_authenticate';
        }
        
        return $targetUrl;
    }
}
