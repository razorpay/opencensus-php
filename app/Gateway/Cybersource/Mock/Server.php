<?php

namespace RZP\Gateway\Cybersource\Mock;

use Str;
use RZP\App;
use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Cybersource\Fields as F;

class Server extends Base\Mock\Server
{
    protected $repo;

    /**
     * @override
     * @return string
     */
    protected function getWsdlFile()
    {
        return dirname(__DIR__) . '/Wsdl/cybstest.wsdl.xml';
    }

    // Dummy function for mock soap client
    public function Security($header)
    {
        return null;
    }

    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        $response = [
            F::MD => $input[F::MD],
            F::PA_RES => 'eNpVUttygjAQfc9XMP0AkiAw',
            F::TERM_URL => $input[F::TERM_URL]
        ];

        $this->content($response, 'callback');

        return $response;
    }

    public function runTransaction($request)
    {
        $request = json_decode(json_encode($request), true);

        switch(true)
        {
            case isset($request[F::PA_ENROLL_SERVICE]):
                $action = 'auth_enroll';
                break;

            case isset($request[F::PA_VALIDATE_SERVICE]):
                $action = 'auth_validate';
                break;

            case isset($request[F::CC_AUTH_SERVICE]):
                $action = 'authorize';
                break;

            case isset($request[F::CC_CAPTURE_SERVICE]):
                $action = 'capture';
                break;

            case isset($request[F::CC_CREDIT_SERVICE]):
                $action = 'refund';
                break;

            case isset($request[F::CC_AUTH_REVERSAL_SERVICE]):
                $action = 'auth_reversal';
                break;

            default:
                assertTrue(false, 'Unrecognized request type');
        }

        $action = camel_case($action);

        return $this->{$action}($request);
    }

    protected function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;
        $response[F::RECEIPT_NUMBER] = random_int(100000, 999999);

        $response[F::CC_AUTH_REPLY] = $this->getDefaultCcAuthReply($input);

        $this->switchAuthorizeCases($input, $response);

        $this->content($response, 'authorize');

        return $response;
    }

    protected function authEnroll($input)
    {
        $this->validateEnrollInput($input);

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;

        $response[F::PA_ENROLL_REPLY] = $this->getDefaultPayerAuthEnrollReply($input);

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->switchEnrollCases($input, $response);

        $this->content($response, 'enrollment');

        return $response;
    }

    protected function authValidate($input)
    {
        $this->validateActionInput($input, 'auth_validate');

        $this->content($input, 'verify_pares');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;

        $response[F::PA_VALIDATE_REPLY] = $this->getDefaultPayerAuthValidateReply($input);

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->switchAuthValidateCases($input, $response);

        $this->content($response, 'auth_validate');

        return $response;
    }

    protected function capture($input)
    {
        parent::capture($input);

        $this->validateActionInput($input);

        $this->content($input, 'validate_capture');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;

        $response[F::CC_CAPTURE_REPLY] = [
            F::REASON_CODE       => 100,
            F::AMOUNT            => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            F::RECONCILIATION_ID => $response[F::REQUEST_ID],
        ];

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->content($response);

        return $response;
    }

    protected function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input);

        $this->content($input, 'validate_refund');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;

        $response[F::CC_CREDIT_REPLY] = [
            F::REASON_CODE       => 100,
            F::AMOUNT            => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            F::RECONCILIATION_ID => $response[F::REQUEST_ID],
            F::REFUND_DATETIME   => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z')
        ];

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->content($response, 'refund');

        return $response;
    }

    protected function authReversal($input)
    {
        parent::reverse($input);

        $this->validateActionInput($input);

        $this->content($input, 'validate_auth_reversal');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];
        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);
        $response[F::REQUEST_TOKEN] = Str::quickRandom(40);
        $response[F::DECISION] = 'ACCEPT';
        $response[F::REASON_CODE] = 100;

        $response[F::CC_AUTH_REVERSAL_REPLY] = [
            F::REASON_CODE        => 100,
            F::AMOUNT             => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            F::PROCESSOR_RESPONSE => $response[F::REQUEST_ID],
            F::REQUEST_DATETIME   => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z')
        ];

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->content($response);

        return $response;
    }

    protected function getDefaultPayerAuthEnrollReply(array $input)
    {
        $paEnrollReply = [
            F::REASON_CODE      => 100,
            F::VERES_ENROLLED   => 'N'
        ];

        $network = Card\Network::detectNetwork($input['card']['accountNumber']);

        switch ($network) {
            case Card\Network::MC:
                $paEnrollReply[F::COMMERCE_INDICATOR] = 'spa';
                $paEnrollReply[F::UCAF_COLLECTION_INDICATOR] = '01';

                break;

            case Card\Network::VISA:
                $paEnrollReply[F::COMMERCE_INDICATOR] = 'vbv_attempted';
                $paEnrollReply[F::ECI] = '06';

                break;
        }

        return $paEnrollReply;
    }

    protected function getDefaultPayerAuthValidateReply(array $input)
    {
        $paEnrollReply = [
            F::REASON_CODE      => 100,
            F::VERES_ENROLLED   => 'N'
        ];

        $network = Card\Network::detectNetwork($input['card']['accountNumber']);

        switch ($network) {
            case Card\Network::MC:
                $paEnrollReply[F::COMMERCE_INDICATOR] = 'spa';
                $paEnrollReply[F::UCAF_COLLECTION_INDICATOR] = '01';

                break;

            case Card\Network::VISA:
                $paEnrollReply[F::COMMERCE_INDICATOR] = 'vbv_attempted';
                $paEnrollReply[F::ECI] = '06';

                break;
        }

        return $paEnrollReply;
    }

    protected function switchEnrollCases(array $input, array &$response)
    {
        $cardNumber = $input[F::CARD][F::ACCOUNT_NUMBER];

        $this->acsUrl = $this->route->getUrl('mock_acs', ['gateway' => 'cybersource']);
        $this->messageId = Str::quickRandom(20);
        $this->proxyPan = (string) random_int(100000, 999999);

        switch ($cardNumber)
        {
            // MasterCard SecureCode Card Enrolled: Attempts Processing
            case 5200000000000106:

            // Verified by Visa Card Enrolled: Attempts Processing
            case 4000000000000000063:
                $authenticationPath = 'ATTEMPTS';

            // MasterCard SecureCode Card Enrolled: Incomplete Authentication
            case 5200000000000031:

            // Verified by Visa Card Enrolled: Incomplete Authentication
            case 4000000000000036:

            // Verified by Visa Card Enrolled: Authentication Error
            case 4000000000000093:
                $authenticationPath = $authenticationPath ?? 'UNKNOWN';

            // MasterCard SecureCode Card Enrolled: Authentication Error
            case 5200000000000098:

            // MasterCard SecureCode Card Enrolled: Successful Authentication
            // But Invalid PARes
            case 5200000000000015:

            // Verified by Visa Card Enrolled: Successful Authentication
            // But Invalid PARes
            case 4000000000000000071:

            // MasterCard SecureCode Card Enrolled: Successful Authentication
            // With authentication window
            case 5200000000000007:

            // Verified by Visa Card Enrolled: Successful Authentication
            // With authentication window
            case 4000000000000002:

            // MasterCard SecureCode Card Enrolled: Unsuccessful Authentication
            case 5200000000000023:

            // Verified by Visa Card Enrolled: Unsuccessful Authentication
            case 4000000000000028:

            // Verified bt Visa Card Enrolled for Recurring payment
            case 4012001038443335:

                $response[F::DECISION] = 'REJECT';
                $response[F::REASON_CODE] = 475;

                $this->enrolled = 'Y';

                $response[F::PA_ENROLL_REPLY] = [
                    F::ACS_URL => $this->acsUrl,
                    F::AUTHENTICATION_PATH => $authenticationPath ?? 'ENROLLED',
                    F::PA_REQ => $this->getPaReq($input),
                    F::PROOF_XML => $this->getProofXml($input, $response),
                    F::PROXY_PAN => $this->proxyPan,
                    F::REASON_CODE => 475,
                    F::VERES_ENROLLED => $this->enrolled,
                    F::XID => base64_encode($this->messageId)
                ];

                break;

                case 42809500000009:
                    $response[F::DECISION] = 'REJECT';
                    $response[F::REASON_CODE] = 476;
                break;

            // MasterCard SecureCode Card Enrolled: Unavailable Authentication
            case 5200000000000064:
                $this->enrolled = 'U';

                $response[F::PA_ENROLL_REPLY] = [
                    F::REASON_CODE               => 100,
                    F::COMMERCE_INDICATOR        => 'spa',
                    F::UCAF_COLLECTION_INDICATOR => '0',
                    F::PROOF_XML                 => $this->getProofXml($input, $response),
                    F::VERES_ENROLLED            => $this->enrolled,
                    F::AUTHENTICATION_PATH       => 'NOREDIRECT'
                ];

                break;

            // MasterCard SecureCode Enrollment Check Error: Error response
            case 5200000000000080:
                $this->enrolled = 'U';

                $response[F::PA_ENROLL_REPLY] = [
                    F::REASON_CODE               => 100,
                    F::COMMERCE_INDICATOR        => 'spa',
                    F::UCAF_COLLECTION_INDICATOR => '1',
                    F::PROOF_XML                 => $this->getProofXml($input, $response),
                    F::VERES_ENROLLED            => $this->enrolled
                ];

                break;

            // Verified by Visa Enrollment Check Error: Error response
            case 4000000000000085:

            // Verified by Visa Enrollment Check Error:
            // Incorrect Configuration: Unable to Authenticate
            case 4000000000000077:

            // Verified by Visa Card Enrolled: Unavailable Authentication
            case 4000000000000000014:

                $this->enrolled = 'U';

                $response[F::PA_ENROLL_REPLY] = [
                    F::REASON_CODE        => 100,
                    F::COMMERCE_INDICATOR => 'internet',
                    F::PROOF_XML          => $this->getProofXml($input, $response),
                    F::VERES_ENROLLED     => $this->enrolled
                ];

                break;

            // Verified by Visa Card Not Enrolled
            case 4000000000000051:
                $this->enrolled = 'N';

                $response[F::PA_ENROLL_REPLY][F::AUTHENTICATION_PATH] = 'NOREDIRECT';
                $response[F::PA_ENROLL_REPLY][F::PROOF_XML] = $this->getProofXml($input, $response);

                break;

        }
    }

    protected function switchAuthorizeCases(array $input, array &$response)
    {
        $cardNumber = $input[F::CARD][F::ACCOUNT_NUMBER];

        $originalCcAuthReply = $response[F::CC_AUTH_REPLY];

        switch ($cardNumber)
        {
            // Verified bt Visa Card Enrolled for Recurring payment
            case 4012001038443335:
                $commerceIndicator = 'recurring';

            // Verified by Visa Card Enrolled: Successful Authentication
            // With authentication window
            case 4000000000000002:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

                unset($response[F::CC_AUTH_REPLY][F::CARD_CATEGORY]);
                unset($response[F::CC_AUTH_REPLY][F::CARD_GROUP]);
                break;

            // Verified by Visa Card Enrolled: Successful Authentication
            // With authentication window
            case 5200000000000007:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

                unset($response[F::CC_AUTH_REPLY][F::CARD_CATEGORY]);
                unset($response[F::CC_AUTH_REPLY][F::CARD_GROUP]);

                break;

            // MasterCard SecureCode Card Enrolled: Incomplete Authentication
            case 5200000000000031:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                break;

            // Verified by Visa Card Enrolled: Incomplete Authentication
            case 4000000000000036:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                break;

            // MasterCard SecureCode Card Enrolled: Attempts Processing
            case 5200000000000106:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                break;

            // Verified by Visa Card Enrolled: Attempts Processing
            case 4000000000000000063:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                      => 'Y',
                    F::AVS_CODE_RAW                  => 'Y',
                    F::CV_CODE                       => 'M',
                    F::CV_CODE_RAW                   => 'M',
                    F::MERCHANT_ADVICE_CODE          => '01',
                    F::MERCHANT_ADVICE_CODE_RAW      => 'M001',
                    F::CAVV_RESPONSE_CODE            => '2',
                    F::CAVV_RESPONSE_CODE_RAW        => '2',
                ]);

                break;

            // MasterCard SecureCode Enrollment Check Error: Error response
            case 5200000000000080:

            // Verified by Visa Enrollment Check Error: Error response
            case 4000000000000085:

            // Verified by Visa Enrollment Check Error:
            // Incorrect Configuration: Unable to Authenticate
            case 4000000000000077:

            // MasterCard SecureCode Card Enrolled: Unavailable Authentication
            case 5200000000000064:

            // Verified by Visa Card Enrolled: Unavailable Authentication
            case 4000000000000000014:

            // Verified by Visa Card Not Enrolled
            case 4000000000000051:

                $response[F::CC_AUTH_REPLY] = array_merge($originalCcAuthReply, [
                    F::AVS_CODE                 => 'Y',
                    F::AVS_CODE_RAW             => 'Y',
                    F::MERCHANT_ADVICE_CODE     => '01',
                    F::MERCHANT_ADVICE_CODE_RAW => 'M001',
                    F::CAVV_RESPONSE_CODE       => '2',
                    F::CAVV_RESPONSE_CODE_RAW   => '2',
                ]);

                unset($response[F::CC_AUTH_REPLY][F::CARD_CATEGORY]);
                unset($response[F::CC_AUTH_REPLY][F::CARD_GROUP]);

                break;
        }
    }

    protected function switchAuthValidateCases(array $input, array &$response)
    {
        $cardNumber = $input[F::CARD][F::ACCOUNT_NUMBER];

        $originalAuthValidateReply = $response[F::PA_VALIDATE_REPLY];

        switch ($cardNumber)
        {
            // Verified by Visa Card Enrolled: Successful Authentication
            // With authentication window
            case 4000000000000002:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '0',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Success',
                    F::CAVV                          => 'AAABAWFlmQAAAABjRWWZEEFgFz+=',
                    F::CAVV_ALGORITHM                => '2',
                    F::COMMERCE_INDICATOR            => $commerceIndicator ?? 'vbv',
                    F::ECI                           => '05',
                    F::ECI_RAW                       => '05',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'Y',
                ];

                $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

                break;

            // Verified by Visa Card Enrolled: Successful Authentication
            // With authentication window
            case 5200000000000007:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '0',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Success',
                    F::UCAF_AUTHENTICATION_DATA      => 'jELUbgG+Tfj0AREACMLdCae+oIs=',
                    F::CAVV_ALGORITHM                => '3',
                    F::COMMERCE_INDICATOR            => 'spa',
                    F::UCAF_COLLECTION_INDICATOR     => '02',
                    F::ECI_RAW                       => '02',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'Y',
                ];

                $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

                break;

            // MasterCard SecureCode Card Enrolled: Successful Authentication
            // But Invalid PARes
            case 5200000000000015:

            // Verified by Visa Card Enrolled: Successful Authentication
            // But Invalid PARes
            case 4000000000000000071:

                $response[F::DECISION] = 'REJECT';
                $response[F::REASON_CODE] = 476;

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 476,
                    F::AUTHENTICATION_RESULT         => '-1',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'PARes signature digest value mismatch. PARes message has been modified',
                    F::XID                           => base64_encode($this->messageId)
                ];

                break;

            // MasterCard SecureCode Card Enrolled: Authentication Error
            case 5200000000000098:

                $response[F::DECISION] = 'REJECT';
                $response[F::REASON_CODE] = 476;

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE               => 476,
                    F::COMMERCE_INDICATOR        => 'internet',
                    F::UCAF_COLLECTION_INDICATOR => '1'
                ];

                break;

            // Verified by Visa Card Enrolled: Authentication Error
            case 4000000000000093:

                $response[F::DECISION] = 'REJECT';
                $response[F::REASON_CODE] = 476;

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE         => 476,
                    F::COMMERCE_INDICATOR  => 'internet',
                    F::ECI                 => '07'
                ];

                break;

            case '42809500000009':

                $response['decision'] = 'REJECT';
                $response['reasonCode'] = 476;
                $response['payerAuthEnrollReply'] = [
                    'reasonCode' => 476
                ];
                $response['missingField'] = 'c:authRequestID';
                $response['requestToken'] = 'AhjjLwSR/H2rNiTcqkX45p6D4dUQCsgfIwdIy6SZbpAeLRGAdmIW';
                break;

            // MasterCard SecureCode Card Enrolled: Unsuccessful Authentication
            case 5200000000000023:

            // Verified by Visa Card Enrolled: Unsuccessful Authentication
            case 4000000000000028:

                $response[F::DECISION] = 'REJECT';
                $response[F::REASON_CODE] = 476;

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 476,
                    F::AUTHENTICATION_RESULT         => '9',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'User failed authentication',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'N'
                ];

                unset($response[F::RECEIPT_NUMBER]);

                break;

            // MasterCard SecureCode Card Enrolled: Incomplete Authentication
            case 5200000000000031:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '6',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Issuer unable to perform authentication',
                    F::COMMERCE_INDICATOR            => 'spa',
                    F::UCAF_COLLECTION_INDICATOR     => '0',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'U'
                ];

                break;

            // Verified by Visa Card Enrolled: Incomplete Authentication
            case 4000000000000036:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '6',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Issuer unable to perform authentication',
                    F::COMMERCE_INDICATOR            => 'internet',
                    F::ECI                           => '07',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'U'
                ];

                break;

            // MasterCard SecureCode Card Enrolled: Attempts Processing
            case 5200000000000106:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '1',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Success',
                    F::CAVV_ALGORITHM                => '3',
                    F::COMMERCE_INDICATOR            => 'spa',
                    F::UCAF_AUTHENTICATION_DATA      => 'hsjuQljfI86bAQAFvVQGaWsBPwI=',
                    F::UCAF_COLLECTION_INDICATOR     => '1',
                    F::ECI_RAW                       => '01',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'A'
                ];

                break;

            // Verified by Visa Card Enrolled: Attempts Processing
            case 4000000000000000063:

                $response[F::PA_VALIDATE_REPLY] = [
                    F::REASON_CODE                   => 100,
                    F::AUTHENTICATION_RESULT         => '1',
                    F::AUTHENTICATION_STATUS_MESSAGE => 'Success',
                    F::CAVV                          => 'BwAQAgJ4IAUFBwdik3ggEETHTsU=',
                    F::CAVV_ALGORITHM                => '2',
                    F::COMMERCE_INDICATOR            => 'vbv_attempted',
                    F::ECI                           => '06',
                    F::ECI_RAW                       => '06',
                    F::XID                           => base64_encode($this->messageId),
                    F::PARES_STATUS                  => 'A'
                ];

                break;
        }
    }

    protected function getDefaultCcAuthReply(array $input)
    {
        $amount = number_format($input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT], 2, '.', '');

        $ccAuthReply = [
            F::AMOUNT                 => $amount,
            F::AUTHORIZATION_CODE     => strtoupper(Str::quickRandom(6)),
            F::AUTHORIZED_DATETIME    => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
            F::AVS_CODE               => 'G',
            F::AVS_CODE_RAW           => 'G',
            F::CARD_CATEGORY          => 'F',
            F::CARD_GROUP             => '0',
            F::CAVV_RESPONSE_CODE     => '3',
            F::CAVV_RESPONSE_CODE_RAW => '3',
            F::CV_CODE                => 'M',
            F::CV_CODE_RAW            => 'M',
            F::PAYMENT_NETWORK_TXN_ID => '306295780' . random_int(100000, 999999),
            F::PROCESSOR_RESPONSE     => '00',
            F::REASON_CODE            => 100,
            F::RECONCILIATION_ID      => $input[F::MERCHANT_REFERENCE_CODE],
        ];

        return $ccAuthReply;
    }

    public function verify($input)
    {
        parent::verify($input);

        $content = $this->getVerifyContent($input);

        $this->content($content, 'verify_content');

        $xml = '';

        if ($content !== [])
        {
            $xml = require __DIR__ . '/VerifyResponseXml.php';
        }

        $this->content($xml, 'verify_xml');

        return $this->makeResponse($xml);
    }

    protected function getVerifyContent(array $input)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                        $input[F::MERCHANT_REFERENCE_NUMBER], Cybersource\Action::AUTHORIZE);

        if ($payment === null)
        {
            return [];
        }

        $amount = ($payment->getAmount() / 100);

        return [
            'ccCaptureService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount
            ],
            'ccAuthService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount,
                'authCode' => strtoupper(Str::quickRandom(6)),
                'eci' => '2',
                'RFlag' => Cybersource\ReplyFlag::SOK
            ],
            'payerAuthEnrollService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount
            ]
        ];
    }

    protected function getProofXml($input, $response)
    {
        $replacePair = [
            ':time:' => Carbon::now()->format('Y M d H:i:s'),
            ':messageId:' => $this->messageId,
            ':pan:' => 'xxxxxxxxxxxxxx' . substr($input[F::CARD][F::ACCOUNT_NUMBER], -4),
            ':acsUrl:' => $this->acsUrl,
            ':enrolled:' => $this->enrolled
        ];

        $proofXmlTemplate = '<AuthProof><Time>:time:</Time><DSUrl>https://api.razorpay.com</DSUrl><VEReqProof><Message id=":messageId:"><VEReq><version>1.0.2</version><pan>:pan:</pan><Merchant><acqBIN>469216</acqBIN><merID>341422420000000</merID><password></password></Merchant><Browser><deviceCategory>0</deviceCategory></Browser></VEReq></Message></VEReqProof><VEResProof><Message id=":messageId:"><VERes><version>1.0.2</version><CH><enrolled>:enrolled:</enrolled><acctID>1064630</acctID></CH><url>:acsUrl:</url><protocol>ThreeDSecure</protocol></VERes></Message></VEResProof></AuthProof>';

        return strtr($proofXmlTemplate, $replacePair);
    }

    protected function getPaReq($input)
    {
        $replacePair = [
            ':date:'          => Carbon::now()->format('Ymd H:i:s'),
            ':messageId:'     => $this->messageId,
            ':displayAmount:' => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            ':amount:'        => (int) ($input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT] * 100),
            ':proxyPan:'      => $this->proxyPan,
            ':xid:'           => base64_encode($this->messageId)
        ];

        $paReqTemplate = '<ThreeDSecure><Message id=":messageId:"><PAReq><version>1.0.2</version>
            <Merchant><acqBIN>469216</acqBIN>
            <merID>341422420000000</merID>
            <name>RAZORPAY TECHNOLOGIES PVT</name>
            <country>356</country>
            <url>http://store.razorpay.com/</url>
            </Merchant><Purchase><xid>:xid:</xid>
            <date>:date:</date>
            <amount>Rs:displayAmount:</amount>
            <purchAmount>:amount:</purchAmount>
            <currency>356</currency>
            <exponent>2</exponent>
            </Purchase><CH><acctID>:proxyPan:</acctID>
            <expiry>2011</expiry>
            </CH></PAReq></Message></ThreeDSecure>';

        $xml = strtr($paReqTemplate, $replacePair);

        $xml = trim($xml);

        $xml = zlib_encode($xml, 15);
        $xml = base64_encode($xml);

        return $xml;
    }

    protected function makeResponse($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}

