<?php

namespace Http;

use Response;
use League\Csv\Writer;
use SplTempFileObject;

class AppResponse
{
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

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     * @param  array $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    protected static function flatten(array $array)
    {

        foreach ($array as &$value)
        {
            if (is_array($value))
            {
                $value = '"'.json_encode($value);
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
