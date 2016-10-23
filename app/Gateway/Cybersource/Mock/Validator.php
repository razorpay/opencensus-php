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
        'ccAuthService.eci'                    => 'sometimes|numeric|digits_between:1,2',
        'ccAuthService.commerceIndicator'      => 'sometimes|in:internet,recurring,vbv_attempted,spa',
        'ccAuthService.veresEnrolled'          => 'sometimes|in:Y,N,U',
        'ucaf'                                 => 'sometimes|array',
        'ucaf.commerceIndicator'               => 'sometimes_if:card.cardType,002|string',
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
        'billTo'                               => 'required|array',
        'billTo.firstName'                     => 'required|string',
        'billTo.lastName'                      => 'required|string',
        'billTo.street1'                       => 'required|string',
        'billTo.city'                          => 'required|string',
        'billTo.state'                         => 'required|string',
        'billTo.postalCode'                    => 'sometimes|numeric',
        'billTo.country'                       => 'required_with:billTo.postalCode|string|size:2',
        'billTo.email'                         => 'required|email',
    ];

    protected static $captureRules = [
        'merchantID'                           => 'required|string|max:30',
        'merchantReferenceCode'                => 'required|string|size:14',
        'ccCaptureService'                     => 'required|array',
        'ccCaptureService.run'                 => 'required|in:true',
        'ccCaptureService.authRequestID'       => 'required|string',
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
        'purchaseTotals'                       => 'required|array',
        'purchaseTotals.currency'              => 'required|string|size:3|in:INR',
        'purchaseTotals.grandTotalAmount'      => 'required|numeric',
    ];
}