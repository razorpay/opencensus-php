<?php

namespace RZP\Gateway\Hdfc\ErrorCodes;

use RZP\Error\ErrorCode;
use RZP\Gateway\Hdfc\Payment\Result;
use RZP\Gateway\Base\ErrorCodes\Cards;

class ErrorCodes extends Cards\ErrorCodes
{

    /**
     * No Response From Visa Directory Server
     * Unable to Verify Enrollment
     * Invalid Response from Directory Server
     * Authentication Not Available
     * Invalid Payer Authentication Response
     *
     * This code can come for any of the above
     * scenarios.
     */
    const FSS0001   = 'FSS0001';

    const FSS00002  = 'FSS00002';

    const GW00150   = 'GW00150';
    const GW00151   = 'GW00151';
    const GW00152   = 'GW00152';
    const GW00153   = 'GW00153';
    const GW00154   = 'GW00154';

    const GW00157   = 'GW00157';

    const GW00159   = 'GW00159';
    const GW00160   = 'GW00160';
    const GW00161   = 'GW00161';
    const GW00162   = 'GW00162';
    const GW00163   = 'GW00163';
    const GW00164   = 'GW00164';
    const GW00165   = 'GW00165';
    const GW00166   = 'GW00166';
    const GW00167   = 'GW00167';

    const GW00170   = 'GW00170';
    const GW00171   = 'GW00171';

    const GW00176   = 'GW00176';
    const GW00177   = 'GW00177';

    const GW00181   = 'GW00181';

    const GW00183   = 'GW00183';

    const GW00201   = 'GW00201';
    const GW00205   = 'GW00205';
    const GW00256   = 'GW00256';
    const GW00258   = 'GW00258';
    const GW00259   = 'GW00259';
    const GW00261   = 'GW00261';

    const GW00456   = 'GW00456';
    const GW00458   = 'GW00458';
    const GW00850   = 'GW00850';
    const GW00852   = 'GW00852';
    const GW00854   = 'GW00854';
    const GW00856   = 'GW00856';
    const GW00874   = 'GW00874';
    const GW00876   = 'GW00876';
    const GW02016   = 'GW02016';

    /**
     * All codes in GV000** series
     * relate to errors in 3-d secure
     */

    /**
     * Unknown VPAS version
     * See bottom of below link to learn about VPAS.
     * We should not encounter it.
     * http://www.businessknowhow.com/money/ecombest.htm
     */
    const GV00001   = 'GV00001';

    const GV00002   = 'GV00002';
    const GV00003   = 'GV00003';
    const GV00004   = 'GV00004';
    const GV00005   = 'GV00005';
    const GV00006   = 'GV00006';
    const GV00007   = 'GV00007';
    const GV00008   = 'GV00008';
    const GV00009   = 'GV00009';
    const GV00010   = 'GV00010';
    const GV00011   = 'GV00011';
    const GV00012   = 'GV00012';
    const GV00013   = 'GV00013';

    const GV00100   = 'GV00100';
    const GV00101   = 'GV00101';
    const GV00102   = 'GV00102';
    const GV00103   = 'GV00103';
    const GV00104   = 'GV00104';

    const CM00030   = 'CM00030';
    const CM90000   = 'CM90000';
    const CM90001   = 'CM90001';
    const CM90002   = 'CM90002';
    const CM90003   = 'CM90003';
    const CM90004   = 'CM90004';
    const CM90005   = 'CM90005';
    const CM900000  = 'CM900000';

    const PY20001   = 'PY20001';
    const PY20002   = 'PY20002';
    const PY20006   = 'PY20006';
    const PY20007   = 'PY20007';
    const PY20085   = 'PY20085';

    /**
     * New error codes after IPAY migration
     */
    const IPAY0200121 = 'IPAY0200121';


    /**
     * Invalid error code.
     * Set it whenever encountering an unknown error code
     */
    const RP00001   = 'RP00001';

    /**
     * When for the given request, the returned 'result' code
     * isn't recognized
     */
    const RP00002   = 'RP00002';

    /**
     * Gateway request timeout
     */
    const RP00003   = 'RP00003';

    /**
     * When response result is 'HOST TIMEOUT'
     */
    const RP00004   = 'RP00004';
    const RP00015   = 'RP00015';

    /**
     * When response result is 'DENIED BY RISK'
     */
    const RP00005   = 'RP00005';

    /**
     * When response result is 'NOT APPROVED'
     */
    const RP00006   = 'RP00006';
    const RP00016   = 'RP00016';

    /**
     * When response result is 'NOT CAPTURED'
     */
    const RP00007   = 'RP00007';
    const RP00017   = 'RP00017';

    /**
     * When response status_code is greater than 500
     * signifying gateway server error
     */
    const RP00008   = 'RP00008';

    /**
     * When response content-type is not
     * application/xml
     */
    const RP00009   = 'RP00009';

    /**
     * When enroll response result code is
     * AUTH ERROR
     */
    const RP00010     = 'RP00010';
    const RP00020     = 'RP00020';

    /**
     * When enroll response result code is
     * CANCELLED
     */
    const RP00011   = 'RP00011';

    /**
     * When enroll response result code is
     * NOT SUPPORTED
     */
    const RP00012   = 'RP00012';
    const RP00018   = 'RP00018';

    /**
     * Gateway request timeout
     * operation timed out specifically
     */
    const RP00013   = 'RP00013';

    /**
     * Any gateway request exception except request timeout.
     */
    const RP00014   = 'RP00014';

    /**
     * Transaction denied due to previous capture check failure
     * ( Validate Original Transaction )
     */

    const RP00021   = 'RP00021';

    public static $resultToErrorCodeMap = array(
        Result::HOST_TIMEOUT        => self::RP00004,
        Result::DENIED_BY_RISK      => self::RP00005,
        Result::NOT_APPROVED        => self::RP00006,
        Result::NOT_CAPTURED        => self::RP00007,
        Result::AUTH_ERROR          => self::RP00010,
        Result::CANCELED            => self::RP00011,
        Result::NOT_SUPPORTED       => self::RP00012,
        Result::HOST_TIMEOUT_IPAY   => self::RP00015,
        Result::NOT_APPROVED_IPAY   => self::RP00016,
        Result::NOT_CAPTURED_IPAY   => self::RP00017,
        Result::NOT_SUPPORTED_IPAY  => self::RP00018,
        Result::DENIED_BY_RISK_IPAY => self::GW00256,
        Result::AUTH_ERROR_IPAY     => self::RP00020,
        Result::DENIED_CAPTURE      => self::RP00021,
    );

    public static $invalidResultErrorCode = self::RP00002;

    public static $hdfcErrorCodeMap = array(
        self::FSS0001     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE,
        self::FSS00002    => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,

        self::GW00150     => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GW00151     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        self::GW00152     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::GW00153     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::GW00154     => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,

        self::GW00157     => ErrorCode::GATEWAY_ERROR_NOT_UNDERSTOOD_ERROR,
        self::GW00159     => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA, //missing card no?
        self::GW00160     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,
        self::GW00161     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME,
        self::GW00162     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_UDF,
        self::GW00163     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS,
        self::GW00164     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP,
        self::GV00104     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::GW00165     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID, //track id?
        self::GW00166     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE, //invalid card no?
        self::GW00167     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::GW00170     => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,

        self::GW00171     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00176     => ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        self::GW00177     => ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,

        self::GW00181     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        self::GW00183     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED,

        self::GW00201     => ErrorCode::GATEWAY_ERROR_SUPPORT_AUTH_NOT_FOUND,
        self::GW00205     => ErrorCode::GATEWAY_ERROR_INVALID_SUBSEQUENT_PAYMENT,
        self::GW00256     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        self::GW00258     => ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN,
        self::GW00259     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,

        self::GW00261     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GW00456     => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,

        self::GW00458     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::GW00850     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::GW00852     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::GW00854     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        self::GW00856     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        self::GW00874     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        self::GW00876     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,

        self::GW02016     => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,

        self::GV00001     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::GV00002     => ErrorCode::GATEWAY_ERROR_CARD_NOT_ENROLLED,

        self::GV00003     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::GV00004     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        self::GV00005     => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00006     => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00007     => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        self::GV00008     => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,

        self::GV00009     => ErrorCode::GATEWAY_ERROR_CERTIFICATE_VALIDATION_FAILED,
        self::GV00010     => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GV00011     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_EXPIRY_DATE,

        self::GV00012     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //invalid action
        self::GV00013     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,

        self::GV00100     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        self::GV00101     => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        self::GV00102     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,

        self::GV00103     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,

        self::PY20001     => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        self::PY20002     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT,
        self::PY20006     => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND,

        self::PY20007     => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        self::PY20085     => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        self::CM00030     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90000     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90001     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::CM90002     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::CM90003     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::CM90004     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::CM90005     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::CM900000    => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::RP00001     => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        self::RP00002     => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        self::RP00003     => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00004     => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00005     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        self::RP00006     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,

        self::RP00007     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,

        self::RP00008     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,

        self::RP00009     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::RP00010     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::RP00011     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL,
        self::RP00012     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        self::RP00013     => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,

        self::RP00014     => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::RP00015     => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        self::RP00016     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,

        self::RP00017     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,

        self::RP00018     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,

        self::RP00020     => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID,
        self::RP00021     => ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
        self::IPAY0200121 => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,


        'GW00100' => ErrorCode::GATEWAY_ERROR_INSTITUTION_ID_MISMATCH, //Institution ID required.
        'GW00101' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND, //Brand ID required.
        'GW00102' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_BRAND, //Brand Description required.
        'GW00155' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Batch Track ID.
        'GW00156' => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST, //Batch track ID not unique.
        'GW00158' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Card Number Not Numeric.
        'GW00168' => ErrorCode::GATEWAY_ERROR_INSTITUTION_ID_MISMATCH, //Institution ID mismatch.
        'GW00169' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Merchant ID mismatch.
        'GW00172' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_CVV, //Card Verification Code Mismatch.
        'GW00173' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code mismatch.
        'GW00174' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Card Number mismatch.
        'GW00175' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Result Code.
        'GW00178' => ErrorCode::GATEWAY_ERROR_PAYMENT_VOID_FAILED,  // Void Greater Than Original Amount check.
        'GW00179' => ErrorCode::GATEWAY_ERROR_PAYMENT_VOID_FAILED, //Failed Previous Voids check.
        'GW00180' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // Failed Previous Credits check.
        'GW00200' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS, //Address verification failed.
        'GW00203' => ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED, //Invalid access: Must use POST method
        'GW00251' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //Maximum transaction count exceeded.
        'GW00252' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //  Maximun transaction volume exceeded.
        'GW00253' => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED, //  Maximum credit volume exceeded.
        'GW00254' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH, //  Maximum card debit volume exceeded.
        'GW00255' => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED, //Maximum card credit volume exceeded.
        'GW00257' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH, //Maximum transaction amount exceeded.
        'GW00260' => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH, //  Transaction denied: Credits exceed Captures.
        'GW00300' => ErrorCode::GATEWAY_ERROR_INSTITUTION_ID_MISMATCH, //Institution ID required.
        'GW00302' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency code required.
        'GW00350' => ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY, //  -> Merchant has terminals. GATEWAY_ERROR_TERMINAL_ERROR
        'GW00351' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Merchant ID required.
        'GW00352' => ErrorCode::GATEWAY_ERROR_INSTITUTION_ID_MISMATCH, // Institution ID required.


        'GW00353' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // Invalid Login.
        'GW00354' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // Invalid Login
        'GW00355' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'New password mismatch.',
        'GW00356' => ErrorCode::BAD_REQUEST_OLD_PASSWORD_MISMATCH, // 'New password same as old.',
        'GW00357' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Console password required.',
        'GW00358' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // Invalid Login.
        'GW00359' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE, //  ISO Country code is invalid.
        'GW00360' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Website address in invalid.',
        'GW00361' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Console Password Confirmation required.',
        'GW00362' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Console Password Confirmation invalid.',
        'GW00363' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Password Confirmation mismatch.',

        'GW00364' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME, //Name is invalid.
        'GW00378' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code is invalid.


        'GW00380' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // Merchant ID not numeric.
        'GW00381' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //  Merchant Password data invalid.

        'GW00383' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant Password Confirmation invalid.
        'GW00384' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant New Password invalid.
        'GW00385' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant New Password is required.
        'GW00386' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant New Confirm Password is required.
        'GW00387' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant User Password is expired.
        'GW00388' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant User Name is required.
        'GW00389' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant User Pswd Confirmation is required.
        'GW00390' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Password and Confirmation mismatch.
        'GW00391' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Merchant User password length is too short.*/

        'GW00478' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Card Acceptor ID.
        'GW00479' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID, //Invalid Terminal Card Acceptor Terminal ID.
        'GW00392' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Merchant User Status is required.
        'GW00480' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Acquirer Institution.
        'GW00393' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, // Merchant User Status is invalid.
        'GW00481' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Base24 Terminal Data.

        'GW00394' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User Password is required.',

        'GW00482' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Retailer ID.

        'GW00395' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User Password mismatch.',
        'GW00483' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Retailer Group ID.

        'GW00396' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User new password same as old.',
        'GW00484' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Retailer Region ID.
        'GW00397' => ErrorCode::GATEWAY_ERROR_USER_INACTIVE, //Merchant User inactive.
        'GW00485' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Cutover Hour.


        'GW00398' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User Password length too long.',
        'GW00486' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Cutover Minute.
        'GW00399' => ErrorCode::BAD_REQUEST_USER_NOT_FOUND, //Merchant User ID is invalid.
        'GW00550' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Category Code missing or invalid.

        'GW00400' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User Password is invalid.',

        'GW00600' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //'Card number required.'

        'GW00401' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // 'Merchant New Password is invalid.',

        'GW00601' => ErrorCode::GATEWAY_ERROR_PAYMENT_BIN_CHECK_FAILED, //Card BIN required.

        'GW00402' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant User Name is invalid.',
        'GW00602' => ErrorCode::GATEWAY_ERROR_PAYMENT_BIN_CHECK_FAILED, //Invalid BIN length.

        'GW00403' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant Password Expire Code is invalid.',
        'GW00603' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Institution ID required.

        'GW00404' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Merchant Password Expires Date is invalid.',
        'GW00604' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Merchant ID required.
        'GW00405' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,//  Merchant exists with this Merchanat Category.
        'GW00605' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID, //Terminal ID required.
        'GW00420' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code data in not available.
        'GW00606' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Card number required.
        'GW00421' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code minor digits is invalid.
        'GW00607' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Invalid Card Number.
        'GW00450' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Institution ID required.
        'GW00608' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Invalid Currency Code.
        'GW00451' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Merchant ID required.

        'GW00609' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Invalid Decline Reason.',

        'GW00452' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID, //Terminal ID required.
        'GW00610' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Invalid Card Number.
        'GW00453' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID, //TranPortal ID required. GATEWAY_ERROR_PAYMENT_MISSING_DATA
        'GW00611' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //'Invalid Negative Reason.',
        'GW00454' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //TranPortal password required.
        'GW00612' => ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN, //Invalid Card Bin.
        'GW00455' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //TranPortal ID not unique.
        'GW00613' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, // Invalid Negative Reason.

        'GW00614' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Please click correct button or tab.',

        'GW00457' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION, //Action not supported.
        'GW00700' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'No processes available.',
        'GW00701' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Batch not processed.
        'GW00459' => ErrorCode::GATEWAY_ERROR_TERMINAL_NOT_ENABLED, //Terminal not active.
        'GW00702' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Batch could not be started.
        'GW00460' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //TranPortal ID required.
        'GW00703' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Institution ID required.
        'GW00461' => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT, //Invalid Transaction amount.

        'GW00704' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Batch ID not numeric.',
        'GW00462' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Invalid Tranportal Password.',
        'GW00705' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Batch ID required.',

        'GW00463' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Institution ID.

        'GW00706' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Invalid Batch Response File Name',

        'GW00464' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Merchant ID.
        'GW00750' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID, //Error hashing card number.
        'GW00465' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Termainl ID.
        'GW00466' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Description. or GATEWAY_ERROR_TERMINAL_ERROR
        'GW00851' => ErrorCode::BAD_REQUEST_ACTION_INVALID_TYPE, //Invalid Action Type.
        'GW00467' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal External Connection ID.
        'GW00468' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Risk Profile.
        'GW00853' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Invalid Card Number.
        'GW00469' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Currency Code List.
        'GW00470' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Action Code List.
        'GW00471' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Payment Instrument List.
        'GW00875' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA, //Missing required data.
        'GW00472' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Brand List.
        'GW00473' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Option Code List.
        'GW00877' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Invalid Card Number.
        'GW00474' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Risk Flag.
        'GW00878' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Invalid Card Number.
        'GW00475' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Address Verification List.
        'GW00879' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_EXPIRY_DATE, //Invalid Expiration Date.
        'GW00476' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Tranportal ID.
        'GW00880' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_CVV, //Invalid Card Verification Code.
        'GW00477' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Status.
        'GW00881' => ErrorCode::GATEWAY_ERROR_INVALID_CARD_TYPE, //Card Type unknown

        'GW00950' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Batch Upload Directory Required.
        'GW00951' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, // Batch Download Directory Required.
        'GW00952' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Batch Archive Directory Required.
        'GW00953' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Access Log Retention Days Required.
        'GW00954' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Transaction Log Retention Days Required.
        'GW00955' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Declined Card Retention Minutes Required.
        'GW00956' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA, //Declined Card Maximum Count Required.
        'GW00957' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Access Log Retention Days Invalid.
        'GW00958' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Transaction Log Retention Days Invalid.
        'GW00959' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Declined Card Retention Minutes Invalid.
        'GW00960' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Declined Card Maximum Count Invalid.
        'GW00961' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_CAPTURE, //Multiple Capture Flag Invalid.
        'GW00962' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_CAPTURE, //Multiple Capture Amount Flag Invalid.
        'GW00963' => ErrorCode::GATEWAY_ERROR_PAYMENT_VOID_FAILED, //Multiple Void Flag Invalid.
        'GW00964' => ErrorCode::GATEWAY_ERROR_PAYMENT_VOID_FAILED, //Compare Void Amount Flag Invalid.
        'GW00965' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Multiple Credit Debit Flag Invalid.
        'GW00966' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Compare Credit Debit Amount Flag Invalid.
        'GW00967' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Batch Upload Directory Invalid.
        'GW00968' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Batch Download Directory Invalid.
        'GW00969' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Batch Archive Directory Invalid.
        'GW00970' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Cutover Hour.
        'GW00971' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL, //Invalid Terminal Cutover Minute.
        'GW00975' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, // FAQ Question ID required.
        'GW00976' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Language ID.
        'GW00977' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Question ID.
        'GW00978' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Question content.
        'GW00979' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, // Invalid Answer content.

        'GW00990' => ErrorCode::GATEWAY_ERROR_CARD_ENCRYPTION_FAILED, //Card Number Encryption Failure.

        'GW01020' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Languange ID.
        'GW01021' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Invalid System News Header.
        'GW01022' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Invalid System News Body.
        'GW01040' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Languange ID.
        'GW01041' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Merchant Guideline Header.
        'GW01042' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Merchant Guideline Body.

        'GW01060' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code Required.

        'GW01061' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Institution ID Required.
        'GW01062' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Ivalid Minor Digits Range.

        'GW01063' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code Not Numeric.
        'GW01064' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Currency Code Not Valid ISO Code.

        'GW01065' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Minor Digits.

        'GW01066' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT, //Invalid Amount.
        'GW01067' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Invalid Currency Code Data.
        'GW01068' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Invalid Currency Description Data.

        'GW01069' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Minor Digits Data.

        'GW01072' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, // 'Merchant exists with this Currency Code.',

        'GW01180' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Hex required.
        'GW01181' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Invalid Key length.
        'GW01182' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Key encryption failed.

        'GW00555' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID, //Terminal ID is Deactivated, Please contact PG Helpdesk.

        'CM00001' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT, //External message timeout.
        'CM00002' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //External message system error.
        'CM00026' => ErrorCode::GATEWAY_ERROR_CONNECTION_ERROR, //External connection ID required.
        'CM00027' => ErrorCode::GATEWAY_ERROR_CONNECTION_ERROR, //External connection description required.
        'CM00028' => ErrorCode::GATEWAY_ERROR_CONNECTION_ERROR, //External connection Protocol code required.
        'CM00029' => ErrorCode::GATEWAY_ERROR_CONNECTION_ERROR, //External connection Formatter class name invalid.

        'CM00051' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Institution ID required.
        'CM00052' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Invalid Institution Data Encryption Key Name.
        'CM00053' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Missing Institution Data Encryption Key.
        'CM00054' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Institution Data Encryption Key does not exist.
        'CM00055' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Missing Institution Data Encryption Key.
        'CM00056' => ErrorCode::GATEWAY_ERROR_RESPONSE_ENCRYPTION_FAILED, //Institution Data Encryption Key does not exist.

        'CM90100' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Message formatter class failure.

        'PY20000' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA, //Missing required data.

        'PY20003' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Order Status.
        'PY20004' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Non Numeric Card Number.
        'PY20005' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NUMBER, //Missing Card Number.

        'PY20008' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Invalid Currency Code.
        'PY20009' => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND, //Transaction Not Found.
        'PY20010' => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL, //Invalid Merchant URL.
        'PY20011' => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL, //Invalid Merchant Error URL.
        'PY20012' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Track ID.
        'PY20013' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Language Code.
        'PY20014' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_UDF, //Invalid User Defined Field.

        'GW01070' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY, //Invalid Currency Symbol Data.
        'GW01071' => ErrorCode::GATEWAY_ERROR_TERMINAL_ERROR, //Terminal exists with this Currency Code.

        'PY20015' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_NAME, //Invalid Card Name.
        'PY20016' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ADDRESS, //Invalid Card Address.
        'PY20017' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_ZIP, //Invalid Zip Code.
        'PY20018' => ErrorCode::GATEWAY_ERROR_CARD_INVALID_CVV, //Invalid Card Verification Code.
        'PY20019' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID, //Invalid Transaction ID.
        'PY20080' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Payment Page Style File.
        'PY20081' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Payment Page Header File.
        'PY20082' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //Invalid Payment Page Footer File.
        'PY20050' => ErrorCode::GATEWAY_ERROR_CARD_ENCRYPTION_FAILED, //Card Number Encryption Failure.

        '412' => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE, //Issuer Authentication Server failure
        '410' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN, //Failed Initiate CheckBin - BIN not present
        '404' => ErrorCode::GATEWAY_ERROR_SQL_ERROR, //SQL Exception
        '408' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //Caught ERROR of type:[ System.Xml.XmlException ] . strXML is not a valid XML string    23

       'IPAY0200301' => ErrorCode::BAD_REQUEST_INVALID_CARD_DETAILS, //Invalid transaction details
       'IPAY0200300' => ErrorCode::BAD_REQUEST_PAYMENT_MISSING_DATA, //Missing transaction details

       'FSS00003' => ErrorCode::GATEWAY_ERROR_INVALID_CARD_TYPE, //Only Debit Card Allowed

        '03' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //System error. Could not process transaction
    );

    public static $authRespCodeErrorMap = [
        '38' => ErrorCode::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
        '79' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Approved Adminitrative transaction',
        '84' => ErrorCode::GATEWAY_ERROR_BANK_NOT_SUPPORTED_BY_SWITCH, //No PBF Available in Switch
        '86' => ErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED, //Invalid Authorisation Type
        '87' => ErrorCode::GATEWAY_ERROR_INVALID_PAYMENT_DATA, //Bad Track Data
        '88' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'PTLF Error',
        '89' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Invalid Route Service',

        'N0' => ErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED, //Unable to Authorise
        'N1' => ErrorCode::GATEWAY_ERROR_INVALID_PAN_LENGTH, //'Invalid pan lengtfh',
        'N2' => ErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED, //'Preauthorisation full',
        'N5' => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS, //Maximum online credit per refund reached
        'N6' => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS, //Maximum refund credit reached
        'N8' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, // Over floor limit
        'N9' => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,//  Maximum number of refund credit

        'O1' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Refaaral file full',
        'O2' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'NEG file problem',
        'O3' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //advance less than minimum
        'O4' => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED, //Over limit table
        'O5' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_PIN, //PIN Required
        'O6' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Mod 10 Check',
        'O7' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Force Post',
        'O8' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Bad PBF',
        'O9' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'NSG file problem',

        'P0' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'CAF problem',
        'P1' => ErrorCode::BAD_REQUEST_CARD_DAILY_LIMIT_REACHED, //Over Daily Limit
        'P3' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //advance less than minimum
        'P4' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED, //Number of times used
        'P5' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY, // declined by bank or gateway? - Decline
        'P6' => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED, //Over limit table
        'P7' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //Advance less than minimum
        'P8' => ErrorCode::GATEWAY_ERROR_TIMED_OUT, //Time out
        //Enter lesser amount. As per bank communication, this is declined by issuer
        'P9' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH_DECLINED_BY_ISSUER,

        'Q0' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_TRANSACTION_DATE, //Invalid transaction date
        'Q2' => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED, //Invalid transaction code
        'Q3' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //Advance less than minimum
        'Q4' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED, //Number times used
        'Q5' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED, //'Delinquent',
        'Q6' => ErrorCode::BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED, //Over limit table
        'Q7' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH, //Amount over maximum
        'Q8' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Administrative card not found',
        'Q9' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Administrative card not allowed',
        'R2' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Approved administrative request performed anytime',
        'R4' => ErrorCode::GATEWAY_ERROR_ACQUIRER_UNAVAILABLE, //  'Chargeback customer file updated, acquirer not found',
        'R5' => ErrorCode::GATEWAY_ERROR_PAYMENT_CHARGEBACK_ERROR, //'Chargeback, incorrect prefix number',
        'R6' => ErrorCode::GATEWAY_ERROR_PAYMENT_CHARGEBACK_ERROR, //'Chargeback, incorrect response code or CPF configuration',
        'R7' => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED, //'Administrative transactions not supported',
        'R8' => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST, //Card on National Negative

        'S4' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'PTLF Full',
        'S5' => ErrorCode::GATEWAY_ERROR_PAYMENT_CHARGEBACK_ERROR, //'Chargeback approved, customer file not updated',
        'S6' => ErrorCode::GATEWAY_ERROR_ACQUIRER_UNAVAILABLE, //  'Chargeback approved, customer file not updated, acquirer not found',
        'S7' => ErrorCode::GATEWAY_ERROR_PAYMENT_CHARGEBACK_ERROR, //'Chargeback accepted, incorrect destination',
        'S8' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'ADMN file problem',
        'S9' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_PIN, //Unable to validate PIN

        'T1' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Invalid credit card advance increment',
        'T2' => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED, //Invalid Transaction code
        'T3' => ErrorCode::GATEWAY_ERROR_UNSUPPORTED_CARD_NETWORK, //Card not supported
        'T4' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH, //Ammount over Maximum
        'T5' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'CAF status  0 or 9',
        'T6' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Bad UAF',
        'T7' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Cash back exceeds daily limit',
        'T8' => ErrorCode::GATEWAY_ERROR_BANK_NOT_SUPPORTED_BY_SWITCH, //  No Such Card / Account in Switch

        'CI' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Compliance error code for issuer in authorization response from NPCI',
        'ED' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'E-commerce decline   in authorization response from NPCI',
        '40' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR, //Requested function not supported.
        '74' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK, //Transactions declined by Issuer based on Risk Score
        'E3' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT, //  ARQC validation failed by Issuer
        'E4' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT, //  TVR validation failed by Issuer
        'E5' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT, // CVR  validation failed by Issuer
        'CA' => ErrorCode::GATEWAY_ERROR_ACQUIRER_UNAVAILABLE, //  Compliance error code for acquirer
        'M6' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Compliance error code for LMM',
        'E1' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'AAC GENERATED',
        'E2' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Terminal does not receive AAC AND TC',
        '32' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Partial Reversal'

        // Denied by risk
        'D' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_AMOUNT_LIMIT_REACHED, //Total Amount limit set for the terminal for transactions has been crossed
        'E' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_TRANSACTION_LIMIT_REACHED, //Total transaction limit set for the terminal has been crossed
        'F' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_AMOUNT_LIMIT_REACHED, //Maximum debit amount limit set for the terminal for a day has been crossed
        'G' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_AMOUNT_LIMIT_REACHED, //Maximum credit amount limit set for the terminal for a day has been crossed
        'H' => ErrorCode::BAD_REQUEST_CARD_DAILY_LIMIT_REACHED, //Maximum debit amount set for per card for rolling 24 hrs has been crossed
        'I' => ErrorCode::BAD_REQUEST_CARD_DAILY_LIMIT_REACHED, //Maximum credit amount set for per card for rolling 24 hrs has been crossed
        'J' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //Maximum transaction set for per card for rolling 24 hrs has been crossed
        'K' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //Amount Less than Minimum Amount configured
        'P' => ErrorCode::GATEWAY_ERROR_SQL_ERROR, //received this in mail: "Due to some DB issue received below response"
        'X' => ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN, //BIN is added as negative BIN in PG
        'Y' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED, //Card is present in negative card list and will be decline in future also unless it is not removed manually.
        'Z' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED, //Card is present in decline card database, will be declined for short time
    ];

    public static function getErrorCodeForResult($result)
    {
        if (isset(self::$resultToErrorCodeMap[$result]))
        {
            return self::$resultToErrorCodeMap[$result];
        }

        return self::$invalidResultErrorCode;
    }

    public static function shouldRetryRefund($authRespCode)
    {
        $retryAuthRespCodes = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'P'];

        return (in_array($authRespCode, $retryAuthRespCodes, true) === true);
    }

    public static function getErrorFieldName($fieldName)
    {
        if ($fieldName === ErrorFields::DUMMY_ERROR_FIELD)
        {
            return ErrorFields::AUTH_RESP_CODE;
        }

        return $fieldName;
    }
}
