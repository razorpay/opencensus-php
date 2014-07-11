<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;
use Requests;
use EE\Exception\GatewayTimeoutException;

class Utility
{
    public static function postRequest($request)
    {
        $options['verify'] = false;

        $timeout = Hdfc\Config::TIMEOUT;

        if (! \App::environment('production'))
        {
            $timeout = 30;
        }

        $options['timeout'] = $timeout;

        $response = null;

        try
        {
            $response = Requests::post(
                            $request['url'],
                            $request['header'],
                            $request['xml'],
                            $options);
        }
        catch(\Requests_Exception $e)
        {
            if (self::checkTimeout($e))
            {
                $exception = new GatewayTimeoutException($e->getMessage(), $e);

                $rp = Hdfc\ErrorCode::RP00002;

                $desc = Hdfc\ErrorCode::$errorMessages[$rp];

                $exception->setGatewayErrorCodeAndDesc(
                    Hdfc\ErrorCode::RP00002,
                    $desc);

                throw $exception;
            }
            else
            {
                throw $e;
            }
        }

        return $response;
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param  Requests_Exception $e The caught requests exception
     *
     * @return boolean               true/false
     */
    protected static function checkTimeout(\Requests_Exception $e)
    {
        $msg = $e->getMessage();

        //
        // check if timeout has occured
        //
        if ((strpos($msg, 'Operation timed out')  !== false) or
            (strpos($msg, 'Network is unreachable') !==false) or
            (strpos($msg, 'Name or service not known') !== false) or
            (strpos($msg, 'Failed to connect') !== false) or
            (strpos($msg, 'Could not resolve host') !== false))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function createXml($array)
    {
        $xml = "";

        foreach ($array as $key => $value)
        {
            $xml .= "<$key>$value</$key>\n";
        }

        return $xml;
    }

    public static function getAndParseError(array &$response)
    {
        $error = self::getFieldFromXML($response['xml'], 'error_code_tag');

        if ($error === null)
        {
            return false;
        }

        $response['error']['code'] = $error;
        $response['error']['service'] = self::getFieldFromXML($response['xml'], 'error_service_tag');
        $response['error']['text'] = self::getFieldFromXML($response['xml'], 'error_text');
        $response['error']['result'] = self::getFieldFromXML($response['xml'], 'result');

        return true;
    }

    public static function parseResponseXml(array &$response)
    {
        if (self::getAndParseError($response))
        {
            return;
        }

        self::getFieldsFromXML(
            $response['xml'],
            $response['fields'],
            $response['data']);
    }

    public static function getFieldsFromXML($xml, $fields, &$array)
    {
        foreach ($fields as $field)
        {
            $array[$field] = GetTextBetweenTags($xml, "<$field>", "</$field>");
        }
    }

    public static function getFieldFromXML($xml, $field)
    {
        return GetTextBetweenTags($xml, "<$field>", "</$field>");
    }

    public static function runRequestResponseFlow(array &$request, array &$response)
    {
        // Create xml from the fields
        $request['xml'] = self::createXml($request['data']);

        // send the request and get response
        $response['response'] = self::postRequest($request);

        $response['xml'] = $response['response']->body;

        self::parseResponseXml($response);
    }

    /**
     * Unsets specified fields
     */
    public static function unsetFields($data, $fields)
    {
        if ($data === null)
            return null;

        $data = array_diff_key($data, array_flip($fields));

        return $data;
    }
}