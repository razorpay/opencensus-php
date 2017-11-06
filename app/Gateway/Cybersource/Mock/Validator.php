<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;

class Validator extends Base\Validator
{
    protected static $authenticateRules = [
        'PaReq'                                => 'required|string',
        'MD'                                   => 'present|string|size:14',
        'TermUrl'                              => 'required|url'
    ];

    protected static $enrollRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'payerAuthEnrollService'               => 'required|array',
        'payerAuthEnrollService.run'           => 'required|in:true',
        'card'                                 => 'required|array',
        'card.accountNumber'                   => 'required|numeric|digits_between:13,19|luhn',
        'card.expirationMonth'                 => 'required_with:card.expirationYear|numeric|digits:2|min:1|max:12',
        'card.expirationYear'                  => 'required_with:card.expirationMonth|numeric|digits:4',
        'card.cardType'                        => 'sometimes|in:001,002',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
    ];

    protected static $authRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'ccAuthService'                        => 'required|array',
        'ccAuthService.run'                    => 'required|in:true',
        'ccAuthService.eciRaw'                 => 'sometimes|numeric|digits_between:1,2',
        'ccAuthService.commerceIndicator'      => 'sometimes|in:internet,recurring,vbv_attempted,vbv,spa',
        'ccAuthService.veresEnrolled'          => 'sometimes|in:Y,N,U',
        'ccAuthService.paresStatus'            => 'sometimes|in:Y,N,A,U',
        'ccAuthService.xid'                    => 'sometimes|string',
        'ccAuthService.cavv'                   => 'sometimes|string',
        'invoiceHeader'                        => 'sometimes|array',
        'invoiceHeader.merchantDescriptor'     => 'sometimes|max:22|alpha_space_num',
        'businessRules'                        => 'sometimes|array',
        'ucaf'                                 => 'sometimes|array',
        'ucaf.commerceIndicator'               => 'sometimes|string',
        'payerAuthValidateService'             => 'sometimes|array',
        'payerAuthValidateService.run'         => 'required_with:payerAuthValidateService|string|in:true',
        'payerAuthValidateService.signedPARes' => 'required_with:payerAuthValidateService|string',
        'card'                                 => 'required|array',
        'card.accountNumber'                   => 'required|numeric|digits_between:13,19|luhn',
        'card.expirationMonth'                 => 'required_with:card.expirationYear|numeric|digits:2|min:1|max:12',
        'card.expirationYear'                  => 'required_with:card.expirationMonth|numeric|digits:4',
        'card.cvNumber'                        => 'sometimes|numeric|digits_between:3,4',
        'card.cardType'                        => 'sometimes|in:001,002',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
        'billTo'                               => 'sometimes|array',
        'billTo.firstName'                     => 'sometimes|string',
        'billTo.lastName'                      => 'sometimes|string',
        'billTo.street1'                       => 'sometimes|string',
        'billTo.city'                          => 'sometimes|string',
        'billTo.state'                         => 'sometimes|string',
        'billTo.postalCode'                    => 'sometimes|numeric',
        'billTo.country'                       => 'required_with:billTo.postalCode|string|size:2',
        'billTo.email'                         => 'sometimes|email',
    ];

    protected static $authValidateRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'payerAuthValidateService'             => 'sometimes|array',
        'payerAuthValidateService.run'         => 'required_with:payerAuthValidateService|string|in:true',
        'payerAuthValidateService.signedPARes' => 'required_with:payerAuthValidateService|string',
        'card'                                 => 'required|array',
        'card.accountNumber'                   => 'required|numeric|digits_between:13,19|luhn',
        'card.expirationMonth'                 => 'required_with:card.expirationYear|numeric|digits:2|min:1|max:12',
        'card.expirationYear'                  => 'required_with:card.expirationMonth|numeric|digits:4',
        'card.cardType'                        => 'sometimes|in:001,002',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR'
    ];

    protected static $captureRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'ccCaptureService'                     => 'required|array',
        'ccCaptureService.run'                 => 'required|in:true',
        'ccCaptureService.authRequestID'       => 'required|string',
        'invoiceHeader'                        => 'sometimes|array',
        'invoiceHeader.merchantDescriptor'     => 'sometimes|max:22|alpha_space_num',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
    ];

    protected static $refundRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'ccCreditService'                      => 'required|array',
        'ccCreditService.run'                  => 'required|in:true',
        'ccCreditService.captureRequestID'     => 'required|string',
        'invoiceHeader'                        => 'sometimes|array',
        'invoiceHeader.merchantDescriptor'     => 'sometimes|max:22|alpha_space_num',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
        'merchantDefinedData'                  => 'sometimes|array',
    ];

    protected static $reverseRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'ccAuthReversalService'                => 'required|array',
        'ccAuthReversalService.run'            => 'required|in:true',
        'ccAuthReversalService.authRequestID'  => 'required|string',
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
        'merchantDefinedData'                  => 'sometimes|array',
    ];
}
