<?php

namespace RZP\Gateway\Mpi\Blade;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;

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
        'Message.VERes.Extension.@attributes.critical'  => 'sometimes'
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
        'Message.PARes.IReq.iReqCode'                                                   => 'sometimes|min:1|max:3',
        'Message.PARes.IReq.iReqDetail'                                                 => 'sometimes|max:2048',
        'Message.PARes.IReq.vendorCode'                                                 => 'sometimes|max:256',
        'Message.PARes.Merchant'                                                        => 'required|array',
        'Message.PARes.Merchant.acqBIN'                                                 => 'required|string|min:1|max:24',
        'Message.PARes.Merchant.merID'                                                  => 'required|string|min:1|max:24',
        'Message.PARes.pan'                                                             => 'required|digits_between:13,19',
        'Message.PARes.Extension'                                                       => 'sometimes',
        'Message.PARes.Extension.@attributes.id'                                        => 'required_with:Message.PARes.Extension',
        'Message.PARes.Extension.@attributes.critical'                                  => 'sometimes',
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

    public static function validateLastFour(string $expected, string $actual)
    {
        $actualLastFour = substr($actual, -4);

        if ($expected !== $actualLastFour)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER,
                '',
                'Invalid PAN provided in pares' . $actual);
        }
    }

    public static function validateResponse(array $response, array $input)
    {
        self::validatePurchaseDate($response, $input);
        self::validateCurrency($response, $input);
        self::validateAmount($response, $input);
        self::validateCurrencyExponent($response, $input);
    }

    public static function validateXid(array $response, string $expectedXid)
    {
        if ($response[PARes::PURCHASE][PARes::XID] !== $expectedXid)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Value mismatch for xid',
                [
                    'expected' => $expectedXid,
                    'actual'   => $response[PARes::PURCHASE][PARes::XID]
                ]
            );
        }
    }

    public static function validatePurchaseDate(array $response, array $input)
    {
        $purchaseDate = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
            ->format('Ymd H:m:s');

        if ($response[PARes::PURCHASE][PARes::DATE] !== $purchaseDate)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Value mismatch',
                [
                    'expected' => $purchaseDate,
                    'actual'   => $response[PARes::PURCHASE][PARes::DATE]
                ]);
        }
    }

    public static function validateCurrency(array $response, array $input)
    {
        $currency = $response[PARes::PURCHASE][PARes::CURRENCY];

        if ($currency !== Currency::getIsoCode($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Invalid currency code',
                [
                    'expected' => $input['payment']['currency'],
                    'actual'   => $currency
                ]);
        }
    }

    public static function validateAmount(array $response, array $input)
    {
        $amount = (int) $response[PARes::PURCHASE][PARes::PURCHASE_AMOUNT];

        if ($amount !== $input['payment']['amount'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Amount mismatch',
                [
                    'expected' => $input['payment']['amount'],
                    'actual'   => $amount
                ]);
        }
    }

    public static function validateCurrencyExponent(array $response, array $input)
    {
        $exponent = (int) $response[PARes::PURCHASE][PARes::EXPONENT];

        if ($exponent !== Currency::getExponent($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Exponent mismatch',
                [
                    'expected' => Currency::getExponent($input['payment']['currency']),
                    'actual'   => $exponent
                ]);
        }
    }

    public static function validatePaymentId(array $response, array $input)
    {
        if ($response[PARes::MESSAGE][PARes::ATTRIBUTES][PARes::ID] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PARES_DATA_MISMATCH,
                '',
                'Payment ID mismatch',
                [
                    'actual'   => $input['payment']['public_id'],
                    'expected' => $response[PARes::MESSAGE][PARes::ATTRIBUTES][PARes::ID]
                ]);
        }
    }
    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
            $messages);
    }
}
