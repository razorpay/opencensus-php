<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantID'                        => 'required|string',
        'merchantReferenceCode'             => 'required|alpha_num',
        'clientLibrary'                     => 'sometimes|string',
        'clientLibraryVersion'              => 'sometimes|string',
        'clientEnvironment'                 => 'sometimes|string',
        'ccAuthService'                     => 'required|array',
        'ccAuthService.run'                 => 'required_with:ccAuthService|string|in:true',
        'ccAuthService.commerceIndicator'   => 'required_with:ccAuthService|string',
        'ccAuthService.reconciliationID'    => 'required_if:ccAuthService.commerceIndicator,internet|string',
        'ccAuthService.eci'                 => 'sometimes|string',
        'billTo'                            => 'required|array',
        'billTo.firstName'                  => 'required|string',
        'billTo.lastName'                   => 'required|string',
        'billTo.street1'                    => 'required|string',
        'billTo.city'                       => 'required|string',
        'billTo.state'                      => 'required|string',
        'billTo.postalCode'                 => 'required|string',
        'billTo.country'                    => 'required_with:billTo.postalCode|string',
        'billTo.email'                      => 'required|email',
        'card'                              => 'required|array',
        'card.accountNumber'                => 'required|string|min:13|max:19',
        'card.expirationMonth'              => 'required|numeric|digits:2',
        'card.expirationYear'               => 'required|numeric|digits:4',
        'card.cvNumber'                     => 'required_if:ccAuthService.commerceIndicator,internet|numeric|digits_between:3,4',
        'purchaseTotals'                    => 'required|array',
        'purchaseTotals.currency'           => 'required|in:INR',
        'purchaseTotals.grandTotalAmount'   => 'required_without:item.unitPrice|numeric',
        'item'                              => 'sometimes|array',
        'item.unitPrice'                    => 'required_without:purchaseTotals.grandTotalAmount|numeric',
        'item.id'                           => 'required_with:item.unitPrice|numeric',
        'ucaf'                              => 'sometimes|array',
        'ucaf.collectionIndicator'          => 'required_with:ucaf',
    );

    protected static $authValidateRules = array(
        'merchantID'                            => 'required|string',
        'merchantReferenceCode'                 => 'required|alpha_num',
        'clientLibrary'                         => 'present|string',
        'clientLibraryVersion'                  => 'present|string',
        'clientEnvironment'                     => 'present|string',
        'payerAuthValidateService'              => 'required|array',
        'payerAuthValidateService.run'          => 'required_with:payerAuthValidateService|string|in:true',
        'payerAuthValidateService.signedPARes'  => 'required_with:payerAuthValidateService|string',
        'billTo'                                => 'required|array',
        'billTo.firstName'                      => 'required|string',
        'billTo.lastName'                       => 'required|string',
        'billTo.street1'                        => 'required|string',
        'billTo.city'                           => 'required|string',
        'billTo.state'                          => 'required|string',
        'billTo.postalCode'                     => 'required|string',
        'billTo.country'                        => 'required|string',
        'billTo.email'                          => 'required|email',
        'card'                                  => 'required|array',
        'card.accountNumber'                    => 'required|string|min:13|max:19',
        'card.expirationMonth'                  => 'required|numeric|digits:2',
        'card.expirationYear'                   => 'required|numeric|digits:4',
        'purchaseTotals'                        => 'required|array',
        'purchaseTotals.currency'               => 'required|in:INR',
        'purchaseTotals.grandTotalAmount'       => 'required_without:item.unitPrice|numeric',
        'item'                                  => 'sometimes|array',
        'item.unitPrice'                        => 'required_without:purchaseTotals.grandTotalAmount|numeric',
        'item.id'                               => 'required_with:item.unitPrice|numeric',
    );

    protected static $captureRules = array(
        'merchantID'                        => 'required|string',
        'merchantReferenceCode'             => 'required|string',
        'ccCaptureService'                  => 'required|array',
        'ccCaptureService.run'              => 'required_with:ccCaptureService|string|in:true',
        'ccCaptureService.authRequestID'    => 'required_with:ccCaptureService|string',
        'card'                              => 'required|array',
        'card.expirationMonth'              => 'required|numeric|digits:2',
        'card.expirationYear'               => 'required|numeric|digits:4',
        'purchaseTotals'                    => 'required|array',
        'purchaseTotals.currency'           => 'required|in:INR',
        'purchaseTotals.grandTotalAmount'   => 'required_without:item.unitPrice|numeric',
        'item'                              => 'sometimes|array',
        'item.unitPrice'                    => 'required_without:purchaseTotals.grandTotalAmount|numeric',
        'item.id'                           => 'required_with:item.unitPrice|numeric',
        'clientLibrary'                     => 'present|string',
        'clientLibraryVersion'              => 'present|string',
        'clientEnvironment'                 => 'present|string'
    );

    protected static $enrollRules = array(
        'payerAuthEnrollService'            => 'required|array',
        'payerAuthEnrollService.run'        => 'required_with:payerAuthEnrollService|string|in:true',
        'card'                              => 'required|array',
        'card.accountNumber'                => 'required|string|between:13,19',
        'card.expirationMonth'              => 'required|numeric|digits_between:1,2|between:1,12',
        'card.expirationYear'               => 'required|numeric|digits:4',
        'purchaseTotals'                    => 'required|array',
        'purchaseTotals.currency'           => 'required|in:INR',
        'purchaseTotals.grandTotalAmount'   => 'required_without:item.unitPrice|numeric',
        'item'                              => 'sometimes|array',
        'item.unitPrice'                    => 'required_without:purchaseTotals.grandTotalAmount|numeric',
        'item.id'                           => 'required_with:item.unitPrice|numeric',
        'merchantID'                        => 'required|string',
        'merchantReferenceCode'             => 'required|string',
        'clientLibrary'                     => 'sometimes|string',
        'clientLibraryVersion'              => 'sometimes|string',
        'clientEnvironment'                 => 'sometimes|string'
    );

    protected static $refundRules = array(
        'ccCreditService'                   => 'required|array',
        'ccCreditService.run'               => 'required_with:ccCreditService|string|in:true',
        'ccCreditService.captureRequestID'  => 'required_with:ccCreditService|string',
        'purchaseTotals'                    => 'required|array',
        'purchaseTotals.currency'           => 'required|in:INR',
        'purchaseTotals.grandTotalAmount'   => 'required_without:item.unitPrice|numeric',
        'item'                              => 'sometimes|array',
        'item.unitPrice'                    => 'required_without:purchaseTotals.grandTotalAmount|numeric',
        'item.id'                           => 'required_with:item.unitPrice|numeric',
        'merchantID'                        => 'required|string',
        'merchantReferenceCode'             => 'required|string',
        'clientLibrary'                     => 'present|string',
        'clientLibraryVersion'              => 'present|string',
        'clientEnvironment'                 => 'present|string'
    );

    protected static $authenticateRules = array(
        'TermUrl'          => 'required|url',
        'MD'               => 'required|string',
        'PaReq'            => 'required|string',
    );

    protected static $verifyRules = array(
        'type'                    => 'required|in:transaction',
        'subtype'                 => 'required|in:transactionDetail',
        'merchantID'              => 'required|string',
        'requestID'               => 'required|string',
        'versionNumber'           => 'required|in:1.90'
    );

    protected static $authValidators = array(
        'cc_auth_service',
        'ucaf'
    );

    protected function validateCcAuthService($input)
    {
        if (isset($input['ccAuthService']['eci']) and
            Card\Network::checkNetwork($input['card']['accountNumber'], 'VISA') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'ECI shouldn\'t be present');
        }
    }

    protected function validateUcaf($input)
    {
        if (isset($input['ucaf']) and
            Card\Network::checkNetwork($input['card']['accountNumber'], 'MC') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'UCAF shouldn\'t be present');
        }
    }
}