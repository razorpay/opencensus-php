<?php

namespace App\Http;

use Response;
use League\Csv\Writer;
use SplTempFileObject;

class AppResponse
{
    /**
     * [security] sensitive
     * https://github.com/razorpay/dashboard/issues/103
     */
    const EXCEL_TRIGGER_CHARS = ['=', '-', '+'];

    public static function jsonResponse($errors, $data = null)
    {
        if (empty($errors))
        {
            $response = array('success' => true);

            if ($data !== null)
            {
                $response = $response + array('data' => $data);
            }
        }
        else
        {
            $response = array('success' => false, 'errors' => $errors);
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

        $headings = array_keys($data[0]);

        $file = new SplTempFileObject();
        $csv = Writer::createFromFileObject($file);

        $csv->insertOne($headings);
        $csv->insertAll($data);

        $response = Response::make($csv);

        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition','attachment;filename=export.csv');
        $response->header('Content-Description', 'File Transfer');

        return $response;
    }
}
