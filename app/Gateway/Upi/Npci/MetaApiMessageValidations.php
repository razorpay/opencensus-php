<?php

namespace RZP\Gateway\Upi\Npci;

class MetaApiMessageValidations
{
    /**
     * This class contains validation codes for the MetaApis
     *
     * Z – Head validation, P – Payer validation, Y – Link validation, X – Txn validation,
     * K – Ac validation, W – Creds validation, N – NewCred validation
     * J – Payee validation, O – Info validation, Q – Device validation,
     * F – ReqRegMob validation, G – HeartBeat validation & other meta validation
     */

    const DEFAULT_ERROR = 'Random error';

    protected static $ReqListPspMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found',
        'U17' => 'PSP is not registered',
        'Z06' => 'MsgId must be present maxlength 35',
    ];

    protected static $ReqListAccPvdMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found',
        'U17' => 'PSP is not registered',
        'Z06' => 'MsgId must be present maxlength 35',
    ];

    protected static $ReqListKeysMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found ',
        'U17' => 'PSP is not registered ',
        'Z06' => 'MsgId must be present maxlength 35',
    ];

    protected static $ReqListAccountMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found',
        'U17' => 'PSP is not registered',
        'Z06' => 'MsgId must be present maxlength 35',
        'P01' => 'Payer not present',
        'P02' => 'Payer.Addr must be valid VPA maxlength 255',
        'P03' => 'Payer.Name alphanumeric minlegth 1 maxlength 99',
        'P04' => 'Payer.SeqNum numeric minlegth 1 maxlength 3',
        'P05' => 'Payer.Type must be present/valid',
        'P06' => 'Payer.Code numeric of length 4',
        'Y01' => 'Link not present',
        'Y02' => 'Link.type must be present/valid',
        'Y03' => 'Link.value must be present/valid',
    ];

    protected static $ReqSetCreMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found',
        'U17' => 'PSP is not registered',
        'Z06' => 'MsgId must be present maxlength 35',
        'P01' => 'Payer not present',
        'P02' => 'Payer.Addr must be valid VPA maxlength 255',
        'P03' => 'Payer.Name alphanumeric minlegth 1 maxlength 99',
        'P04' => 'Payer.SeqNum numeric minlegth 1 maxlength 3',
        'P05' => 'Payer.Type must be present/valid',
        'P06' => 'Payer.Code numeric of length 4',
        'K01' => 'Payer/Payee.Ac must be present',
        'K02' => 'Payer/Payee .Ac.AddrType must be present',
        'K03' => 'Payer/Payee .Ac.Detail must be present',
        'K04' => 'Payer/Payee .Ac.Name must be present',
        'K05' => 'Payer/Payee .Ac.Detail.Aadhar must be present or not valid',
        'K06' => 'Payer/Payee .Ac.Detail.Account must be present or not valid',
        'K07' => 'Payer/Payee .Ac.Detail.Mobile must be present or not valid',
        'K08' => 'Payer/Payee .Ac.Detail.Card must be present or not valid',
        'K09' => 'Payer/Payee .Ac.Detail.Value must be present for / Name',
        'W01' => 'Payer/Payee .Creds not present',
        'W02' => 'Payer/Payee .Creds.Cred must be present',
        'W03' => 'Payer/Payee.Cred data is wrong',
        'W04' => 'Payer/Payee .Cred.Aadhar must be present',
        'W05' => 'Payer/Payee .Cred.Otp must be present',
        'W06' => 'Payer/Payee .Cred.Pin must be present',
        'W07' => 'Payer/Payee .Cred.Card must be present',
        'W08' => 'Payer/Payee .Cred.PreApproved must be present',
        'W09' => 'Payer/Payee .Cred.Data must be present',
        'W10' => 'Payer/Payee . .Cred.Data encrypted authentication must be present',
        'N01' => 'Payer/Payee New .Creds not present',
        'N02' => 'Payer/Payee New .Creds.Cred must be present',
        'N03' => 'Payer/Payee New .Cred data is wrong',
        'N04' => 'Payer/Payee New .Cred.Aadhar must be present',
        'N05' => 'Payer/Payee New .Cred.Otp must be present',
        'N06' => 'Payer/Payee New .Cred.Pin must be present',
        'N07' => 'Payer/Payee New .Cred.Card must be present',
        'N08' => 'Payer/Payee New .Cred.PreApproved must be present',
        'N09' => 'Payer/Payee New .Cred.Data must be present',
        'N10' => 'Payer/Payee New .Cred.Data encrypted authentication must be present',
    ];

    protected static $ReqChkTxnMap = [
        'Z02' => 'Ver numeric/decimal Min length 1 Max length 6',
        'Z03' => 'Ts must be ISO_ZONE format',
        'U52' => 'PSP orgId not found',
        'U17' => 'PSP is not registered',
        'Z06' => 'MsgId must be present maxlength 35',
        'X01' => 'Txn not present ',
        'X02' => 'Txn.Id must be present maxlength 35',
        'X03' => 'Txn.Note alphanumeric; minlength 1 maxlength 50',
        'X04' => 'Txn.RefId alphanumeric; minlength 1 maxlength 35',
        'X06' => 'Txn.Ts must be ISO_ZONE format',
        'X07' => 'Txn.Type must be present/valid',
    ];

    protected static $otherMetaMessageMap = [
        'J01' => 'Payee not present',
        'J02' => 'Payee.Addr must be valid VPA, maxlength 255',
        'J03' => 'Payee.Name alphanumeric, minlegth 1 maxlength 99',
        'J04' => 'Payee.SeqNum numeric, minlegth 1 maxlength 3',
        'J07' => 'Payee.Code numeric of length 4',
        'J08' => 'Payee.Type must be valid',
        'O01' => 'Payer/Payee.Info must be present',
        'O02' => 'Payer/Payee .Info.Identity must be present',
        'O03' => 'Payer/Payee.Info.Identity.Type must be present, minlegth 1, maxlength 20',
        'O04' => 'Payer/Payee .Info.Identity verifiedName must be present, alphanumeric, minlegth 1, maxlength 99',
        'O05' => 'Payer/Payee .Info.Rating whitelisted must be present, minlegth 1, maxlength 5',
        'Q01' => 'Payer/Payee.Device must be present',
        'Q02' => 'Payer/Payee. Device.Tags must be present',
        'Q03' => 'Payer/Payee.Tag.Device.Name/value must be present',
        'Q04' => '- Q11 Same validation message based on device type',
        'F01' => 'RegDetails must be present',
        'F02' => 'RegDetails.Detail must be present',
        'F03' => 'RegDetails.Detail name/value should be present',
        'F04' => 'RegDetails.Detail name not valid',
        'F05' => 'RegDetails.Cred not present',
        'F06' => 'RegDetails.Cred data is wrong',
        'F07' => 'RegDetails.Cred.Otp must be present',
        'F08' => 'RegDetails.Cred.Pin must be present',
        'F09' => 'RegDetails.Cred.Data must be present',
        'F10' => 'RegDetails.Cred.Data encrypted authentication must be present',
        'G01' => 'HbtMsg must be present',
        'G02' => 'HbtMsg.type must be present/valid',
        'G03' => 'value not valid for HbtMsg.type',
        'G04' => 'value not valid for HbtMsgResp.result',
        'G05' => 'value not valid for HbtMsgResp.errorCode',
        'G11' => 'VaeList.Vae.op/name must be present/valid',
        'G12' => 'VaeList.Vae.name must be present, maxlength 99',
        'G13' => 'VaeList.Vae.addr must be valid VPA, maxlength 255',
        'G14' => 'VaeList.Vae.logo must be valid, maxlength 255',
        'G15' => 'VaeList.Vae.url must be valid url, maxlength 255',
        'G21' => 'ReqMsg must be present',
        'G22' => 'ReqMsg.type must be present/valid',
        'G23' => 'value not valid for ReqMsg.type',
        'G24' => 'ReqMsg.Addr must be valid VPA, maxlength 255',
        'G25' => 'Certificate not found',
        'G26' => 'Signature error',
        'G27' => 'Signature mismatch',
        'G51' => '<Resp> must be present',
        'G52' => 'Resp.MsgId must be present, maxlength 35',
        'G53' => 'Resp.Result must be present alphanumeric, Min length 1, Max length 20',
        'G54' => 'Resp.ErrorCode must be present',
        'G55' => 'Resp.ErrorCode should not be present',
        'G56' => 'Resp.MaskName must be present; minlength 1, maxlength 99',
        'G61' => 'Payer.Info must be present',
        'G62' => 'Payer.Info.Identity must be present',
        'G63' => 'Payer.Info.Identity.Type must be present, minlegth 1, maxlength 20',
        'G64' => 'Payer.Info.Identity verifiedName must be present, alphanumeric, minlegth 1, maxlength 99',
        'G65' => 'Payer.Info.Rating verifiedAddress must be present, minlegth 1, maxlength 5',
        'G66' => 'Account Reference number must be present/valid',
        'G67' => 'Account type must be present/valid',
        'G68' => 'Account aeba must be present/valid',
        'G69' => 'Account mbeba must be present/valid',
        'G70' => 'Account IFSC must be present/valid',
        'G71' => 'Masked Account number must be present/valid',
        'G72' => 'Account MMID must be present/valid',
        'G73' => 'Creds Allowed must be present/valid',
    ];

    public static function getMetaApiValidationMessage($code, $api)
    {
        $map = $api . 'Map';

        if (isset(self::$$map[$code]) === true)
        {
            return self::$$map[$code];
        }
        else if (isset(self::$otherMetaMessageMap[$code]) === true)
        {
            return self::$otherMetaMessageMap[$code];
        }

        return self::DEFAULT_ERROR;
    }
}
