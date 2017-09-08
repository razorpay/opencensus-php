<?php

namespace RZP\Gateway\Blade;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;

class Validator extends JitValidator
{
    // Using array because of complex regex
    public static $veresRules = [
        'Message'                                       => 'required|array',
        'Message.@attributes.id'                        => 'required|max:128',
        'Message.VERes.CH'                              => 'required|array',
        'Message.VERes.CH.enrolled'                     => 'required|in:Y,N,U',
        'Message.VERes.version'                         => ['required','min:3','regex:"(1.0.[2-9])|(1.[1-9].[0-9])"'],
        'Message.VERes.CH.acctID'                       => 'required_if:Message.VERes.CH.enrolled,Y|min:1|max:28',
        'Message.VERes.vendorCode'                      => 'sometimes',
        'Message.VERes.url'                             => 'required_if:Message.VERes.CH.enrolled,Y|url|max:2048',
        'Message.VERes.protocol'                        => 'required_if:Message.VERes.CH.enrolled,Y|min:0|max:12|in:ThreeDSecure',
        'Message.VERes.Extension'                       => 'sometimes',
        'Message.VERes.Extension.@attributes.id'        => 'required_with:Message.VERes.Extension',
        'Message.VERes.Extension.@attributes.critical'  => 'sometimes|critical'
    ];

    public static $CRresRules = [
        'Message'                                       => 'required|array',
        'Message.@attributes.id'                        => 'required|max:128',
        'Message.CRRes.version'                         => ['required','min:3','regex:"(1.0.[2-9])|(1.[1-9].[0-9])"'],
        'Message.CRRes.CR'                              => 'sometimes|array',
        'Message.CRRes.serialNumber'                    => 'sometimes|digits_between:1,20',
        'Message.CRRes.IReq'                            => 'sometimes|array',
        'Message.CRRes.IReq.iReqCode'                   => 'required_with:Message.CRRes.IReq|min:1|max:3',
        'Message.CRRes.IReq.iReqDetail'                 => 'sometimes|max:2048',
        'Message.CRRes.IReq.vendorCode'                 => 'sometimes|max:256',
    ];

    public static $paresRules = [
        'Message'                                                                       => 'required|array',
        'Message.@attributes.id'                                                        => 'required|max:128',
        'Message.PARes.@attributes.id'                                                  => 'required|max:128',
        'Message.PARes.version'                                                         => ['required','min:3','regex:"(1.0.[2-9])|(1.[1-9].[0-9])"'],
        'Message.PARes.TX'                                                              => 'required|array',
        'Message.PARes.TX.time'                                                         => 'required|date_format:Ymd H:i:s',
        'Message.PARes.TX.status'                                                       => 'required|size:1|in:Y,N,U,A|',
        'Message.PARes.TX.eci'                                                          => 'required_if:Message.PARes.TX.status,Y,A|between:0,2',
        'Message.PARes.TX.cavv'                                                         => 'required_if:Message.PARes.TX.status,Y,A|size:28', //TODO add cavv custom validator
        'Message.PARes.TX.cavvAlgorithm'                                                => 'required_with:Message.PARes.TX.cavv|in:0,1,2,3',
        'Message.PARes.Purchase'                                                        => 'required|array',
        'Message.PARes.Purchase.xid'                                                    => 'required|size:28',
        'Message.PARes.Purchase.purchAmount'                                            => 'required|digits_between:1,12',
        'Message.PARes.Purchase.currency'                                               => 'required|string|size:3',
        'Message.PARes.Purchase.date'                                                   => 'required|date_format:Ymd H:i:s',
        'Message.PARes.Purchase.exponent'                                               => 'required|digits_between:1,1',
        'Message.PARes.IReq'                                                            => 'sometimes|array',
        'Message.PARes.IReq.iReqCode'                                                   => 'required_with:Message.PARes.IReq|min:1|max:3',
        'Message.PARes.IReq.iReqDetail'                                                 => 'required_with:Message.PARes.IReq|max:2048',
        'Message.PARes.IReq.vendorCode'                                                 => 'sometimes|max:256',
        'Message.PARes.Merchant'                                                        => 'required|array',
        'Message.PARes.Merchant.acqBIN'                                                 => 'required|string|min:1|max:24',
        'Message.PARes.Merchant.merID'                                                  => 'required|string|min:1|max:24',
        'Message.PARes.pan'                                                             => 'required|digits_between:13,19',
        'Message.PARes.Extension'                                                       => 'sometimes',
        'Message.PARes.Extension.@attributes.id'                                        => 'required_with:Message.PARes.Extension',
        'Message.PARes.Extension.@attributes.critical'                                  => 'sometimes|critical',
        'Message.Signature'                                                             => 'sometimes|array',
        'Message.Signature.SignedInfo'                                                  => 'sometimes|array',
        'Message.Signature.SignedInfo.@attributes.xmlns'                                => 'sometimes',
        'Message.Signature.SignedInfo.CanonicalizationMethod'                           => 'sometimes',
        'Message.Signature.SignedInfo.CanonicalizationMethod.@attributes.Algorithm'     => 'sometimes',
        'Message.Signature.SignedInfo.SignatureMethod'                                  => 'sometimes',
        'Message.Signature.SignedInfo.SignatureMethod.@attributes.Algorithm'            => 'sometimes',
        'Message.Signature.SignedInfo.Reference'                                        => 'sometimes',
        'Message.Signature.SignedInfo.Reference.@attributes.URI'                        => 'sometimes',
        'Message.Signature.SignedInfo.Reference.DigestMethod'                           => 'sometimes',
        'Message.Signature.SignedInfo.Reference.DigestMethod.@attributes.Algorithm'     => 'sometimes',
        'Message.Signature.SignedInfo.Reference.DigestValue'                            => 'sometimes',
        'Message.Signature.SignatureValue'                                              => 'sometimes',
        'Message.Signature.KeyInfo'                                                     => 'sometimes|array',
        'Message.Signature.KeyInfo.X509Data'                                            => 'sometimes|array',
        'Message.Signature.KeyInfo.X509Data.X509Certificate'                            => 'sometimes|array',
    ];

    public static function validateLastFour($expected, $actual)
    {
        $expectedLastFour = substr($expected, -4);
        $actualLastFour = substr($actual, -4);

        if ($expectedLastFour !== $actualLastFour)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER,
                '',
                'Invalid PAN provided in pares' . $actual);
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
            $messages);
    }

    public static function validateResponse($response, $input)
    {
        self::validatePurchaseDate($response, $input);
        self::validateCurrency($response, $input);
        self::validateAmount($response, $input);
        self::validateCurrencyExponent($response, $input);
        self::validatePaymentId($response, $input);
    }

    public static function validateXid($response, $expectedXid)
    {
        if ($pARes['Purchase']['xid'] !== $expectedXid)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Value mismatch for xid',
                [
                    'expected' => $expectedXid,
                    'actual'   => $response['Purchase']['xid']
                ]
            );
        }
    }

    public static function validatePurchaseDate($response, $input)
    {
        $purchaseDate = Carbon::createFromTimestamp($input['payment']['created_at'], 'Asia/Kolkata')
            ->format('Ymd H:m:s');

        if ($response['Purchase']['date'] !== $purchaseDate)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Value mismatch',
                [
                    'expected' => $purchaseDate,
                    'actual'   => $response['Purchase']['date']
                ]);
        }
    }

    public static function validateCurrency($response, $input)
    {
        $currency = $response['Purchase']['currency'];

        if ($currency !== Currency::getIsoCode($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Invalid currency code',
                [
                    'expected' => $input['payment']['currency'],
                    'actual'   => $currency
                ]);
        }
    }

    public static function validateAmount($response, $input)
    {
        $amount = (int) $response['Purchase']['purchAmount'];

        if ($amount !== $input['payment']['amount'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Amount mismatch',
                [
                    'expected' => $input['payment']['amount'],
                    'actual'   => $amount
                ]);
        }
    }

    public static function validateCurrencyExponent($response, $input)
    {
        $exponent = (int) $response['Purchase']['exponent'];

        if ($exponent !== Currency::getExponent($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Exponent mismatch',
                [
                    'expected' => Currency::getExponent($input['payment']['currency']),
                    'actual'   => $exponent
                ]);
        }
    }

    public static function validatePaymentId($response, $input)
    {
        if ($response['Message']['@attributes']['id'] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Payment ID mismatch',
                [
                    'actual'   => $input['payment']['public_id'],
                    'expected' => $response['@attributes']['id']
                ]);
        }
    }
}
