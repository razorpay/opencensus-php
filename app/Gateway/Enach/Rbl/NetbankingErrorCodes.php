<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class NetbankingErrorCodes
{
    //NPCI emandate register error codes
    const R151 = '151';
    const R152 = '152';
    const R153 = '153';
    const R154 = '154';
    const R155 = '155';
    const R156 = '156';
    const R157 = '157';
    const R158 = '158';
    const R159 = '159';
    const R160 = '160';
    const R161 = '161';
    const R162 = '162';
    const R163 = '163';
    const R164 = '164';
    const R165 = '165';
    const R166 = '166';
    const R167 = '167';
    const R168 = '168';
    const R169 = '169';
    const R170 = '170';
    const R171 = '171';
    const R172 = '172';
    const R173 = '173';
    const R174 = '174';
    const R175 = '175';
    const R176 = '176';
    const R177 = '177';
    const R178 = '178';
    const R179 = '179';
    const R180 = '180';
    const R181 = '181';
    const R182 = '182';
    const R183 = '183';
    const R184 = '184';
    const R185 = '185';
    const R186 = '186';
    const R187 = '187';
    const R188 = '188';
    const R189 = '189';
    const R190 = '190';
    const R191 = '191';
    const R192 = '192';
    const R193 = '193';
    const R194 = '194';
    const R195 = '195';
    const R196 = '196';
    const R197 = '197';
    const R198 = '198';
    const R199 = '199';
    const R200 = '200';
    const R201 = '201';
    const R202 = '202';
    const R203 = '203';
    const R204 = '204';
    const R205 = '205';
    const R206 = '206';
    const R207 = '207';
    const R208 = '208';
    const R209 = '209';
    const R210 = '210';
    const R211 = '211';
    const R212 = '212';
    const R213 = '213';
    const R214 = '214';
    const R215 = '215';
    const R216 = '216';
    const R217 = '217';
    const R218 = '218';
    const R219 = '219';
    const R220 = '220';
    const R221 = '221';
    const R222 = '222';
    const R251 = '251';
    const R252 = '252';
    const R253 = '253';
    const R254 = '254';
    const R255 = '255';
    const R256 = '256';
    const R257 = '257';
    const R258 = '258';
    const R259 = '259';
    const R260 = '260';
    const R261 = '261';
    const R262 = '262';
    const R263 = '263';
    const R264 = '264';
    const R265 = '265';
    const R266 = '266';
    const R267 = '267';
    const R268 = '268';
    const R269 = '269';
    const R270 = '270';
    const R271 = '271';
    const R272 = '272';
    const R273 = '273';
    const R274 = '274';
    const R275 = '275';
    const R276 = '276';
    const R277 = '277';
    const R278 = '278';
    const R279 = '279';
    const R280 = '280';
    const R281 = '281';
    const R282 = '282';
    const R283 = '283';
    const R284 = '284';
    const R285 = '285';
    const R286 = '286';
    const R287 = '287';
    const R288 = '288';
    const R289 = '289';
    const R290 = '290';
    const R291 = '291';
    const R292 = '292';
    const R293 = '293';
    const R294 = '294';
    const R295 = '295';
    const R296 = '296';
    const R297 = '297';
    const R298 = '298';
    const R299 = '299';
    const R300 = '300';
    const R301 = '301';
    const R302 = '302';
    const R303 = '303';
    const R305 = '305';

    protected static $emandateregisterErrorCodeDescMappings = [
        self::R151 => 'Merchant Xmlns name empty or incorrect',
        self::R152 => 'Merchant MsgId empty or incorrect',
        self::R153 => 'Merchant CreDtTm empty or incorrect',
        self::R154 => 'Merchant ReqInitPty Id empty or incorrect',
        self::R155 => 'Merchant CatCode empty or incorrect',
        self::R156 => 'Merchant UtilCode empty or incorrect',
        self::R157 => 'Merchant CatDesc empty or incorrect',
        self::R158 => 'Merchant ReqInitPty name empty or incorrect',
        self::R159 => 'Merchant MndtReqId empty or incorrect',
        self::R160 => 'Merchant SeqTp empty or incorrect',
        self::R161 => 'Merchant Frqcy empty or incorrect',
        self::R162 => 'Merchant FrstColltnDt empty or incorrect',
        self::R163 => 'Merchant FnlColltnDt empty or incorrect',
        self::R164 => 'Merchant ColltnAmt ccy type empty or incorrect',
        self::R165 => 'Merchant ColltnAmt empty or incorrect',
        self::R166 => 'Merchant MaxAmt ccy type empty or incorrect',
        self::R167 => 'Merchant MaxAmt empty or incorrect',
        self::R168 => 'Merchant Creditor name empty or incorrect',
        self::R169 => 'Merchant Creditor Acc No empty or incorrect',
        self::R170 => 'Merchant Creditot MmbId empty or incorrect',
        self::R171 => 'Merchant MnadateReqId empty or incorrect',
        self::R172 => 'Merchant Creditor Acc No empty',
        self::R173 => 'Merchant Info not available',
        self::R174 => 'Merchant ReqInitPty not available',
        self::R175 => 'Merchant Creditor Acc Details not available',
        self::R176 => 'Merchant GrpHdr not available',
        self::R177 => 'Merchant Mndt not available',
        self::R178 => 'Merchant MndtAuthReq empty or not available',
        self::R179 => 'Merchant CheckSum validation failed',
        self::R180 => 'Merchant Signature validation failed',
        self::R181 => 'Error in decrypting Creditor Acc No',
        self::R182 => 'Error in decrypting FrstColltnDt',
        self::R183 => 'Error in decrypting FnlColltnDt',
        self::R184 => 'Error in decrypting ColltnAmt',
        self::R185 => 'Error in decrpting MaxAmt',
        self::R186 => 'Merchant Invalid request',
        self::R187 => 'Merchant Id empty or incorrect',
        self::R188 => 'Merchant ManReqDoc incorrect',
        self::R189 => 'Merchant CheckSum empty or not available',
        self::R190 => 'Merchant Signature not found',
        self::R191 => 'Merchant GrpHdr missing some tag',
        self::R192 => 'Merchant ReqInitPty missing some tag',
        self::R193 => 'Merchant Mndt missing some tag',
        self::R194 => 'Merchant CrAccDtl missing some tag',
        self::R195 => 'Merchant Certificate not found',
        self::R196 => 'Merchant Signature algorithm incorrect',
        self::R197 => 'Merchant Signature Digest algorithm incorrect',
        self::R198 => 'Merchant first date is after final date',
        self::R199 => 'Merchant CrAccDtl not available',
        self::R200 => 'Merchant first date not available',
        self::R201 => 'Merchant final date not available',
        self::R202 => 'Merchant first date empty',
        self::R203 => 'Merchant final date empty',
        self::R204 => 'Merchant ManReqDoc empty not available',
        self::R206 => 'Merchant ColltnAmt and MaxAmt empty',
        self::R207 => 'Merchant ColltnAmt and MaxAmt exist',
        self::R251 => 'Bank Request is invalid',
        self::R252 => 'Bank xmlns is empty or incorrect',
        self::R253 => 'Bank Response type is not available or empty',
        self::R254 => 'Bank Check sum is not available or empty',
        self::R255 => 'Bank Mandate request document is incorrect',
        self::R256 => 'Bank id not available or empty',
        self::R257 => 'Error in decrypting Accepted value',
        self::R258 => 'Error in decrypting Accepted Ref  Number',
        self::R259 => 'Error in decrypting Reason Code',
        self::R260 => 'Error in decrypting Reason Discription',
        self::R261 => 'Error in decrypting Rejected By',
        self::R263 => 'Error code not available in Error Xml',
        self::R264 => 'Error description not available in Error Xml',
        self::R265 => 'Rejected By not available in Error Xml',
        self::R266 => 'Mandate Error Resp not available in Error Xml',
        self::R267 => 'CheckSum validation failed',
        self::R268 => 'Bank UndrlygAccptncDtls not available',
        self::R269 => 'Bank GrpHdr empty or not available',
        self::R270 => 'Bank MsgId empty or incorrect',
        self::R205 => 'MerchantId not in approved list',
        self::R271 => 'Bank CreDtTm empty or incorrect',
        self::R272 => 'Bank ReqInitPty empty or incorrect',
        self::R273 => 'Bank OrgnlMsgInf not available',
        self::R274 => 'Bank MndtReqId empty or incorrect',
        self::R275 => 'Bank UndrlygAccptncDtls CreDtTm empty or incorrect',
        self::R276 => 'Bank  Accptd empty',
        self::R277 => 'Bank AccptRefNo empty or incorrect',
        self::R278 => 'Bank RjctRsn not available',
        self::R279 => 'Bank RjctRsn ReasonCode not empty',
        self::R280 => 'Bank RjctRsn ReasonDesc not empty',
        self::R281 => 'Bank RjctRsn RejectBy not empty',
        self::R282 => 'Bank RjctRsn ReasonCode empty or incorrect',
        self::R283 => 'Bank RjctRsn ReasonDesc empty or incorrect',
        self::R284 => 'Bank RjctRsn RejectBy empty or incorrect',
        self::R285 => 'Bank Certificate  not found',
        self::R286 => 'Bank IFSC Code incorrect',
        self::R287 => 'Bank RespType is incorrect',
        self::R288 => 'Bank GrpHdr missing some tags',
        self::R289 => 'Bank UndrlygAccptncDtls missing some tags',
        self::R290 => 'Bank OrgnlMsgInf missing some tags',
        self::R291 => 'Bank IFSC tag is missing',
        self::R292 => 'Bank DBTR not available',
        self::R293 => 'Bank AccptncRslt not available',
        self::R294 => 'Bank RjctRsn missing some tags',
        self::R295 => 'Bank ManReqDoc not available or empty',
        self::R296 => 'Bank Accptd type incorrect',
        self::R297 => 'Bank Signature not available',
        self::R298 => 'Bank Signature Digest algorithm incorrect',
        self::R299 => 'Bank Signature validation failed',
        self::R300 => 'Bank Signature algorithm incorrect',
        self::R262 => 'Bank NPCI Ref id empty or incorrect',
        self::R301 => 'BankId not in approved list',
        self::R208 => 'Merchant Catcode not in approved list',
        self::R209 => 'Merchant MsgId is duplicate',
        self::R302 => 'Bank MsgId is duplicate',
        self::R210 => 'Merchant Frequency type is invlid',
        self::R211 => 'Merchant Sequence type is invlid',
        self::R212 => 'Merchant Cat Description is not approved list',
        self::R303 => 'Bank Accepted Ref number is duplicate',
        self::R213 => 'Merchant UtilCode is not in approved list',
        self::R214 => 'Merchant Req Pay ID not in approved list',
        self::R215 => 'Merchant Creditor Acc Details name not in approved list',
        self::R216 => 'Merchant Occurences is empty',
        self::R217 => 'Merchant Debitor name empty or incorrect',
        self::R218 => 'Merchant Debitor Account number empty or incorrect',
        self::R219 => 'Merchant Debitor is missing some tags',
        self::R220 => 'Merchant Debitor Acc No empty',
        self::R221 => 'Merchant Debitor Acc  not available',
        self::R222 => 'Merchant Creditor and Debitor account number is same',
        self::R305 => 'Bank Response time out',
    ];

    protected static $emandateRegisterErrorCodeMappings = [
        self::R151 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R152 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R153 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R154 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R155 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R156 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R157 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R158 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R159 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R160 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R161 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R162 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R163 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R164 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::R165 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::R166 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::R167 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::R168 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R169 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R170 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R171 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R172 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R173 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R174 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R175 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R176 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R177 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R178 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R179 => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        self::R180 => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::R181 => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        self::R182 => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        self::R183 => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        self::R184 => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        self::R185 => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        self::R186 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R187 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R188 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R189 => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        self::R190 => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::R191 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R192 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R193 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R194 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R195 => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::R196 => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::R197 => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::R198 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R199 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R200 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R201 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R202 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R203 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R204 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R206 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::R207 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::R205 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R208 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R209 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::R210 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R211 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R212 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R213 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R214 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R215 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R216 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R217 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::R218 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::R219 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R220 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::R221 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::R222 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::R305 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
    ];

    public static function getEmandateRegisterErrorDescriptionFromCode($code)
    {
        $defaultErrorCode = ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED;

        $errorCode = self::$emandateRegisterErrorCodeMappings[$code] ?? $defaultErrorCode;

        return self::getDescriptionFromErrorCode($errorCode);
    }

    protected static function throwInvalidResponseErrorIfCodeNotMapped($errorCode, array $mapping, array $content)
    {
        if (isset($mapping[$errorCode]) === false)
        {
            // Log the whole row, that way it'd be easier to debug based on token id or
            // payment id in case it fails
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Gateway response code mapping not found.',
                $content);
        }
    }

    protected static function getDescriptionFromErrorCode($code)
    {
        $error = new Error($code);

        return $error->getDescription();
    }
}
