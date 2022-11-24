<?php

namespace RZP\Gateway;


use App;
use Request;
use RZP\Exception;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Payment\Gateway;
use RZP\Trace\ApiTraceProcessor;


class Utility
{
    protected $trace;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    public static function checkTimeout(\WpOrg\Requests\Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occurred
        //
        if ((strpos($msg, 'operation timed out') !== false) or
            (strpos($msg, 'network is unreachable') !== false) or
            (strpos($msg, 'name or service not known') !== false) or
            (strpos($msg, 'failed to connect') !== false) or
            (strpos($msg, 'could not resolve host') !== false) or
            (strpos($msg, 'resolving timed out') !== false) or
            (strpos($msg, 'name lookup timed out') !== false) or
            (strpos($msg, 'connection timed out') !== false) or
            (strpos($msg, 'aborted due to timeout') !== false))
        {
            return true;
        }

        return false;
    }

    public static function checkActualTimeout(\WpOrg\Requests\Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occurred
        //
        if (strpos($msg, 'operation timed out') !== false)
        {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the SoapFault exception is a timeout exception
     * If so, this should be treated as a gateway failure
     *
     * @param \SoapFault $sf
     *
     * @return bool
     */
    public static function checkSoapTimeout(\SoapFault $sf)
    {
        $msg = strtolower($sf->getMessage());

        if ((strpos($msg, 'could not connect to host') !== false) or
            (strpos($msg, 'connection timed out') !== false) or
            (strpos($msg, 'error fetching http headers') !== false) or
            (strpos($msg, 'connection reset by peer') !== false)
        )
        {
            return true;
        }

        return false;
    }

    public static function isXml($xml)
    {
        $xml = trim($xml);

        return (mb_substr($xml, 0, 5) === '<?xml');
    }

    public static function stripEmailSpecialChars(string $email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }

    public static function jsonToArray($json)
    {
        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    public static function scrubCardDetails(& $context, $app)
    {
        try
        {
            $cardRegex = $app['config']['trace']['regex']['card_regex'];

            if (empty($cardRegex) === true)
            {
                $cardRegex = ApiTraceProcessor::CARD_REGEX;
            }

            array_walk_recursive($context, function(&$item) use ($cardRegex) {

                if (is_string($item) === true)
                {
                    if (preg_match_all($cardRegex, $item, $matches) !== false)
                    {

                        $matches = $matches[0];

                        foreach ($matches as $match)
                        {
                            $item = str_replace($match, 'CARD_NUMBER_SCRUBBED' . '(' . strlen($match) . ')', $item);
                        }
                    }
                }
            });
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SENSITIVE_BANKING_DETAILS_REDACTION_FAILURE_EXCEPTION
            );
        }
    }

    /**
     * Trace Gateway callback after masking PCI/PII data.
     *
     * @param gateway string
     * @param traceInput array
     *
     * @return null
     */
    public function gatewayTrace($gateway, $traceInput)
    {
        unset($traceInput['payeeVpa'], $traceInput['payerVpa']); // keeping as it was earlier

        $customInput = []; // Define your custom gateway input fields to redact.
        $traceData = []; // Define as data which need to traced.

        // common fields to be redacted
        $redactInput = [
            'phone',
            'email',
            'card_no',
            'phone_no',
            'lastname',
            'firstname',
            'phone_number',
            'address1',
            'address2',
            'city',
            'state',
            'country',
            'zipcode'
        ];

        switch ($gateway)
        {
            CASE Gateway::PAYU:
                $customInput = ['field1', 'field3'];
                $traceData = [
                    'input'     => $traceInput,
                    'headers'   => Request::header(),
                    'gateway'   => $gateway,
                ];
                break;

            default:
                $traceData = [
                    'input'     => $traceInput,
                    'body'      => Request::getContent(),
                    'headers'   => Request::header(),
                    'gateway'   => $gateway,
                ];
        }

        $redactInput = array_merge($redactInput, $customInput);

        foreach ($redactInput as $data)
        {
            if(array_key_exists($data, $traceInput) === false)
            {
                continue;
            }
            $traceInput[$data] = mask_by_percentage(($traceInput[$data]??""),1.0);
        }

        $traceData['input'] = $traceInput; // Reassigning traceData after masking.

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, $traceData);
    }
}
