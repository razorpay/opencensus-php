<?php

class HdfcGatewayFields
{
    /**
     * This is encoded details of the transaction, merchant
     * has to POST this information to the issuing bank
     * ACS as given by the Payment Gateway. Merchant
     * should ensure that proper encoding is done and the
     * values received by Payment Gateway in pareq by
     * merchant are posted to Issuing Bank ACS, no
     * modification is done for the same. Merchant will
     * receive PaReq from Payment Gateway for the Card
     * Enrollment Verification Request, for ENROLLED
     * status merchant should forward the PaReq to Issuing
     * Bank ACS for other result codes merchant can
     * ignore the PaReq field-values
     */
    const PAREQ = 'PAReq';

    /**
     * Electronic Commerce Indicator of the transaction.
     * Merchant will receive this value from Payment
     * gateway for Not enrolled cases, merchant should
     * pass value received from Gateway back to gateway
     * during authorization request. The matrix used should
     * be as –
     * If Visa/Diners Card Type is NOT Enrolled – Value
     * “06”
     * If MasterCard/Maestro Card Type is NOT Enrolled –
     * value “1”
     * In case merchant does not receives ECI value then
     * merchant should pass value “7” irrespective of the
     * card type.
     */
    const ECI = 'eci';

    /**
     * This is Issuing Bank ACS URL where
     * customer/browser has to be redirected for 3 D
     * Secure Authentication. While redirecting merchant
     * sends values like PAReq, MD and Merchant Term
     * URL. This URL will be received by merchant from
     * Payment Gateway only if the result of Card
     * Enrollment is ENROLLED
     */
    const BANK_ACS_URL = 'url';

    /**
     * The result parameters contain transaction request
     * result. On basis of the result received merchant takes
     * the action on the transaction. The result parameter is
     * received in both Card Enrollment Verification
     * Response and Final Response, from the value
     * merchant can identify the further course of action
     * required. Check Section 3.i for information on
     * different result codes from Payment Gateway
     */
    const RESULT = 'result';

    /**
     * The resulting authorization number of the transaction
     * from the issuing bank. This number or series of
     * letters is used for referential purposes by some
     * acquiring/issuing bank/institutions and should be
     * stored properly
     */
    const AUTH_NUMBER = 'auth';
}