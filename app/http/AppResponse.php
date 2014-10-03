<?php

namespace Http;

use Response;

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
}