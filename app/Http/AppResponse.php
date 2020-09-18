<?php

namespace App\Http;

use Debugbar;
use Response;
use League\Csv\Writer;
use SplTempFileObject;
use App\Trace\TraceCode;

class AppResponse
{
    const NON_JSON_ROUTES = [
        'admin_catchall',
        'dashboard',
        'signup',
        'admin_merchant_stats',
        'razorx_catchall',
        'report_download',
        'extension_catchall'
    ];

    /**
     * [security] sensitive
     * https://github.com/razorpay/dashboard/issues/103
     */
    const EXCEL_TRIGGER_CHARS = ['=', '-', '+'];

    public static function jsonResponse($errors, $data = null, $httpCode = null)
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

        return Response::json($response);
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
