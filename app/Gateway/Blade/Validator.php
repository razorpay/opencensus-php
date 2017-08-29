<?php

namespace RZP\Gateway\Blade;

use App\Gateway\Base;
use RZP\Exception;

class Validator
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

    public static $PAresRules = [
        'Message'                                                                       => 'required|array',
        'Message.@attributes.id'                                                        => 'required|max:128',
        'Message.PARes.@attributes.id'                                                  => 'required|max:128',
        'Message.PARes.version'                                                         => ['required','min:3','regex:"(1.0.[2-9])|(1.[1-9].[0-9])"'],
        'Message.PARes.TX'                                                              => 'required|array',
        'Message.PARes.TX.time'                                                         => 'required|date_format:Ymd H:i:s',
        'Message.PARes.TX.status'                                                       => 'required|size:1|in:Y,N,U,A|',
        'Message.PARes.TX.eci'                                                          => 'required_if:Message.PARes.TX.status,Y,A|size_in:0,2',
        'Message.PARes.TX.cavv'                                                         => 'required_if:Message.PARes.TX.status,Y,A|size_wo_whitespace:28|cavv',
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
        'Message.Signature.@attributes.xmlns'                                           => 'required|url',
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
            throw new Exception\BadRequestValidationFailureException(
                    'Invalid PAN provided in pares', 'PAN', $actual);
        }
    }
}
