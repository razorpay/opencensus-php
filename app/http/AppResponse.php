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
    protected static function flatten(array $array, $prefix = '')
    {
        $result = array();

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $result + self::flatten($value, $prefix . $key . '_');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
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
            $row = self::flatten($row);
            $csvMergeObject = $csvMergeObject + $row;
        }

        $headings = array_keys($csvMergeObject);
        sort($headings);

        $file = new SplTempFileObject();

        $csv = Writer::createFromFileObject($file);

        // This makes sure that we have uniform array keys for all rows
        // and that no key is missing for any row
        $csv->addFormatter(function ($row) use($headings) {
            // Add extra keys to the array
            $keysToAdd = array_diff($headings, array_keys($row));

            foreach ($keysToAdd as $key)
            {
                $row[$key] = null;
            }

            // Sort the array by key
            ksort($row);
            return $row;
        });

        // We will force headings to become a key/pair so our formatter
        // doesn't screw with them
        $newHeadings = [];
        foreach ($headings as $heading) {
            $newHeadings[$heading] = $heading;
        }

        $csv->insertOne($newHeadings);

        $csv->insertAll($data);

        $response = Response::make($csv);

        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition','attachment;filename=export.csv');
        $response->header('Content-Description', 'File Transfer');

        return $response;
    }
}
