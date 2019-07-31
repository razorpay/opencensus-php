<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Gateway\Hdfc;

final class Result
{
    /**
     * Result codes received in response for card enrollment
     */

    const ENROLLED      = 1;

    const NOT_ENROLLED  = 2;

    const INITIALIZED   = 3;

    /**
     * This is special case as in,
     * the authentication is not available
     * but instead of returning error fields,
     * the error response is returned in 'result'
     * field, because you know, <bleep> logic!
     */
    const FSS0001_ENROLLED = -1;

    /**
     * This should be absolutely not encountered
     * in the wild. Otherwise, lo and behold
     * you might have discovered yet another gem
     * of the hdfc/fssnet gateway.
     */
    const UNKNOWN_ERROR_ENROLLED = -2;


    /*
     |------------------------------------------------------------------------------------------
     | Result
     |------------------------------------------------------------------------------------------
     | Source: FSSNeTPG-Tranportal Integration Non3D Version 3.1.pdf Page 20
     | Note: Take it as a guideline, not rule.
     |
     | The result parameter in the Hdfc Payment Gateway payment response enables the merchant
     | to determine the payment status. The Merchant is advised to firstly check for any errors
     | received in the response message and if not check for the result parameter. On the baasis
     | of the result parameter, the merchant’s system determines whether payment is
     | Approved or Declined.
     | Mentioned below are the Response Result parameter values that could be
     | received from the Payment Gateway in the result parameter to the merchant in the
     | payment response message.
     | • CAPTURED - Payment was successful (For Action Code “1”, “2” , “5”) i.e. PURCHASE, REFUND and CAPTURE
     | • APPROVED - Payment was successful (For Action Code “4”) i.e. AUTHORIZE
     | • NOT CAPTURED - Payment was failed (For Action Code “1”, “2” , “5”) i.e. PURCHASE, REFUND and CAPTURE
     | • NOT APPROVED - Payment was failed (For Action Code “4”) i.e. AUTHORIZE
     | • DENIED BY RISK - Risk denied the payment processing
     | • HOST TIMEOUT - The authorization system did not respond within the Time out limit
     | • AUTH ERROR - For certain cases when in case card is invalid.
     | • NOT SUPPORTED - For cases where card network is not supported
     | • SUCCESS – The payment is successful (For Action Code “8” i.e. "INQUIRY" if original requested
     | payment is successful at Payment Gateway)
     | Payment Gateway Services
     | • FAILURE(NOT CAPTURED) – The payment is failed (For Action Code “8” i.e. "INQUIRY", if
     | the original payment is failed at Payment Gateway)
     | • FAILURE(SUSPECT) – The payment data is not matching, and hence failed. (For
     | action code “8" i.e. INQUIRY, if the input requested in request is not matching with data available in
     | Payment Gateway, then this result is thrown by Payment Gateway for inquiry
     | payment)
     */

    /**
     * Result codes received in response for payment
     */
    const CAPTURED            = 'CAPTURED';
    const APPROVED            = 'APPROVED';
    const SUCCESS             = 'SUCCESS';
    const NOT_CAPTURED        = 'NOT CAPTURED';
    const NOT_APPROVED        = 'NOT APPROVED';
    const DENIED_BY_RISK      = 'DENIED BY RISK';
    const HOST_TIMEOUT        = 'HOST TIMEOUT';
    const AUTH_ERROR          = 'AUTH ERROR';
    const CANCELED            = 'CANCELED';
    const NOT_SUPPORTED       = 'NOT SUPPORTED';
    const AUTH_ERROR_IPAY     = 'AUTH+ERROR';
    const NOT_SUPPORTED_IPAY  = 'NOT+SUPPORTED';
    const NOT_CAPTURED_IPAY   = 'NOT+CAPTURED';
    const NOT_APPROVED_IPAY   = 'NOT+APPROVED';
    const DENIED_BY_RISK_IPAY = 'DENIED+BY+RISK';
    const HOST_TIMEOUT_IPAY   = 'HOST+TIMEOUT';
    const DENIED_CAPTURE      = 'Transaction denied due to previous capture check failure ( Validate Original Transaction )';

    protected static $successResultCodes = array(
        self::APPROVED,
        self::CAPTURED,
        // We get SUCCESS only for Rupay and maybe for purchase action.
        self::SUCCESS,
    );

    public static function getResultCode($result)
    {
        $success = true;

        switch ($result)
        {
            case 'ENROLLED':
                $result = self::ENROLLED;
                break;
            case 'NOT ENROLLED':
                $result = self::NOT_ENROLLED;
                break;
            case 'INITIALIZED':
                $result = self::INITIALIZED;
                break;
            case 'FSS0001-Authentication Not Available':
                $result = self::FSS0001_ENROLLED;
                $success = false;
                break;
            case 'AUTH ERROR':
                $result = self::AUTH_ERROR;
                $success = false;
                break;
            case 'AUTH+ERROR':
                $result = self::AUTH_ERROR_IPAY;
                $success = false;
                break;
            default:
                $result = self::UNKNOWN_ERROR_ENROLLED;
                $success = false;
        }

        return [$result, $success];
    }

    public static function getEnrollmentStatus($result)
    {
        switch ($result)
        {
            case self::ENROLLED:
                return 'Y';

            case self::NOT_ENROLLED:
                return 'N';

            case self::INITIALIZED:
                return 'Y';

            default:
                return 'F';
        }
    }

    public static function getPreAuthResultCode($result)
    {
        $success = true;

        switch ($result)
        {
            case 'APPROVED':
                $result = self::APPROVED;
                break;
            case 'CAPTURED':
                $result = self::CAPTURED;
                break;
            case 'SUCCESS':
                $result = self::SUCCESS;
                break;
            default:
                $success = false;
        }

        return [$result, $success];
    }

    public static function isResultCodeIndicatingSuccess($result)
    {
        return in_array($result, self::$successResultCodes);
    }

    public static function modifySpecificResultValueIfRequired(& $result)
    {
        // We get SUCCESS only for rupay and maybe for purchase action.
        if ($result === self::SUCCESS)
        {
            $result = Result::CAPTURED;
        }

        //
        // Sometimes hdfc sends result codes as "FAILURE(<Actual code>)"
        // We need to get the actual code from within the small brackets
        //
        if (substr($result, 0, 8) === 'FAILURE(')
        {
            preg_match('~\((.*)\)~', $result, $matches);

            $result = $matches[1];
        }
    }
}
