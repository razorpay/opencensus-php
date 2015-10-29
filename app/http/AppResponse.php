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

    public static function csvResponse(array $data)
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne($data['headings']);
        $csv->insertAll($data['items']);

        $response = Response::make($csv);

        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition','attachment;filename=export.csv');
        $response->header('Content-Description', 'File Transfer');

        return $response;
    }
}
