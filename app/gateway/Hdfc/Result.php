<?php

namespace Gateway\Hdfc;

use Gateway\Hdfc;

final class Result
{
    /**
     * Result codes received in response for card enrollment
     */

    const ENROLLED = 1;

    const NOT_ENROLLED = 2;

    /**
     * This is special case as in,
     * the authentication is not available
     * but instead of returning error fields,
     * the error response is returned in 'result'
     * field, because you know, fuck logic!
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
     | The result parameter in the Hdfc Payment Gateway transaction response enables the merchant
     | to determine the transaction status. The Merchant is advised to firstly check for any errors
     | received in the response message and if not check for the result parameter. On the baasis
     | of the result parameter, the merchant’s system determines whether transaction is
     | Approved or Declined.
     | Mentioned below are the Response Result parameter values that could be
     | received from the Payment Gateway in the result parameter to the merchant in the
     | transaction response message.
     | • CAPTURED - Transaction was successful (For Action Code “1”, “2” , “5”) i.e. PURCHASE, REFUND and CAPTURE
     | • APPROVED - Transaction was successful (For Action Code “4”) i.e. AUTHORIZE
     | • NOT CAPTURED - Transaction was failed (For Action Code “1”, “2” , “5”) i.e. PURCHASE, REFUND and CAPTURE
     | • NOT APPROVED - Transaction was failed (For Action Code “4”) i.e. AUTHORIZE
     | • DENIED BY RISK - Risk denied the transaction processing
     | • HOST TIMEOUT - The authorization system did not respond within the Time out
     | limit
     | • SUCCESS – The transaction is successful (For Action Code “8” i.e. "INQUIRY" if original requested
     | transaction is successful at Payment Gateway)
     | Payment Gateway Services
     | • FAILURE(NOT CAPTURED) – The transaction is failed (For Action Code “8” i.e. "INQUIRY", if
     | the original transaction is failed at Payment Gateway)
     | • FAILURE(SUSPECT) – The transaction data is not matching, and hence failed. (For
     | action code “8" i.e. INQUIRY, if the input requested in request is not matching with data available in
     | Payment Gateway, then this result is thrown by Payment Gateway for inquiry
     | transaction)
     */

    /**
     * Result codes received in response for txn
     */
    const CAPTURED = 'CAPTURED';
    const APPROVED = 'APPROVED';
    const NOT_CAPTURED = 'NOT CAPTURED';
    const NOT_REFUNDED = 'NOT REFUNDED';
    const NOT_APPROVED = 'NOT APPROVED';
    const DENIED_BY_RISK = 'DENIED BY RISK';
    const HOST_TIMEOUT = 'HOST TIMEOUT';

    public static function getResultCode($result)
    {
        $success = true;

        switch ($result)
        {
            case 'ENROLLED':
                $result = Hdfc\Result::ENROLLED;
                break;
            case 'NOT ENROLLED':
                $result = Hdfc\Result::NOT_ENROLLED;
                break;
            case 'FSS0001-Authentication Not Available':
                $result = Hdfc\Result::FSS0001_ENROLLED;
                $success = false;
                break;
            default:
                $result = Hdfc\Result::UNKNOWN_ERROR_ENROLLED;
                $success = false;
        }

        return array($result, $success);
    }
}