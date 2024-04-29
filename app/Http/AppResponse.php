<?php

namespace App\Http;

use Request;
use Response;
use Debugbar;
use App\Metrics\Constants;
use League\Csv\Writer;
use SplTempFileObject;
use App\Trace\TraceCode;
use App\Http\ApiUrl;

class AppResponse
{
    const NON_JSON_ROUTES = [
        'admin_catchall',
        'dashboard',
        'signup',
        'admin_merchant_stats',
        'razorx_catchall',
        'capital_catchall',
        'report_download',
        'extension_catchall',

        //
        // adding this is because in case if user is not authenticated then
        // we should be redirecting to login page
        // and once the login is successful we should revisit this again
        //
        'user_identity',
    ];

    /**
     * [security] sensitive
     * https://github.com/razorpay/dashboard/issues/103
     */
    const EXCEL_TRIGGER_CHARS = ['=', '-', '+'];

    public static function jsonResponse($errors, $data = null, $httpCode = null, $headers = null)
    {
        $response = array();

        // Add httpCode of downstream API
        if (empty($httpCode) === false)
        {
            $response += array('status_code' => $httpCode);
        }

        if (empty($errors))
        {
            $response += array('success' => true);

            if ($data !== null)
            {
                $response += array('data' => $data);
            }
        }
        else
        {
            $response += array('success' => false, 'errors' => $errors);
        }
        self::pushDownstreamMetrics($response);

        if (empty($headers) === false)
        {
            return Response::json($response, 200, $headers);
        }

        return Response::json($response);
    }

    public static function pushDownstreamMetrics($response)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $dimensions = self::getDownstreamMetricsDimensions($response);

            $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);
        }
        catch (\Throwable $t)
        {
            $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    protected static function getDownstreamMetricsDimensions($response)
    {
        $request = app('request');

        return [
            Constants::LABEL_HTTP_REQUESTS_ORIGIN                 => ApiUrl::getRequestOrigin(),
            Constants::LABEL_HTTP_REQUESTS_DOMAIN                 => $request->server->get('SERVER_NAME') ?? 'unknown_domain',
            Constants::LABEL_HTTP_REQUESTS_DOWNSTREAM_STATUS      => $response['status_code']  ?? $response['http_status_code']  ?? 'unknown_status',
            Constants::LABEL_HTTP_REQUESTS_DOWNSTREAM_IS_SUCCESS  => $response['success']                                     ?? 'unknown_success',
            Constants::LABEL_HTTP_REQUESTS_DOWNSTREAM_ROUTE       => $request->route() !== null ? $request->route()->getName() :  'unknown_route',
            Constants::LABEL_HTTP_REQUESTS_DOWNSTREAM_CONTROLLER  => $request->route() !== null ? $request->route()->getAction()['controller']  :  'unknown_controller',
            Constants::LABEL_API_BASE_URL                         => ApiUrl::getApiBaseUrl(),
        ];
    }

    public static function notFoundResponse($error)
    {
        $response = [
            'success'   => false,
            'data'      => $error
        ];
        return Response::json($response, 404);
    }

    public static function unauthorizedResponse($error, $routeName, $url = '/')
    {
        if (in_array($routeName, self::NON_JSON_ROUTES, true) === true)
        {
            return redirect($url);
        }

        $app = \App::getFacadeRoot();

        $trace = $app['trace'];

        // Debugging Unauthorized exception
        $trace->info(TraceCode::USER_UNAUTHORIZED, [
            'error' => $error,
            'route' => $routeName,
            'url'   => $url
        ]);

        return response($error, 401);
    }

    public static function validationErrorResponse($error)
    {
        $response = [
            'success'   => false,
            'data'      => $error
        ];

        $metricsData = $response;

        $metricsData['status_code'] = 400;

        self::pushDownstreamMetrics($metricsData);

        return Response::json($response, 400);
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     * @param  array $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    public static function flatten(array $array)
    {

        foreach ($array as &$value)
        {
            if (is_array($value))
            {
                $value = '"'.json_encode($value);
            }
            else if (is_string($value))
            {
                // This will return "" for all non-string values
                // like NULL, false etc
                $firstCharacter = substr(trim($value), 0, 1);

                // Escape the value if it starts with a trigger
                // character as per EXCEL.
                if (in_array($firstCharacter, self::EXCEL_TRIGGER_CHARS))
                {
                    $value = "'" . $value;
                }
            }
        }

        return $array;
    }

    public static function csvResponse(array $data)
    {

        // We need to disable debugbar because we are using
        // csv->output, which Debugbar can pollute with its
        // HTML not knowing it is a csv response.

        Debugbar::disable();
        // Flatten all the inner keys
        // So internal arrays (like notes)
        // are converted properly
        //
        // The csvMerge Object contains a key/value pair that keeps the first
        // value of every time the key was seen
        $headings = $csvMergeObject = [];

        foreach ($data as &$row)
        {
            // This just converts array fields to JSON
            $row = self::flatten($row);
        }

        $headings = isset($data[0]) ? array_keys($data[0]) : [];

        $file = new SplTempFileObject();
        $csv = Writer::createFromFileObject($file);

        $csv->insertOne($headings);
        $csv->insertAll($data);

        $csv->output('export.csv');
    }
}
