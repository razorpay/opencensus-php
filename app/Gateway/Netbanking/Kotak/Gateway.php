<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Admin\ConfigKey;
use RZP\Gateway\Netbanking\Kotak\AESCrypto;
use RZP\Gateway\Netbanking\Base\Entity as E;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use AuthorizeFailed;

    protected $gateway = 'netbanking_kotak';

    protected $bank = 'kotak';

    protected $tpv;

    protected $sortRequestContent = false;

    const newIntegration = 'kotak_new_integration';

    protected $fields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'TransactionDescription',
        'Checksum',
    );

    protected $map = array(
        'MessageCode'            => 'reference1',
        'DateTimeInGMT'          => 'date',
        'MerchantId'             => 'merchant_code',
        'TraceNumber'            => E::VERIFICATION_ID,
        'Amount'                 => 'amount',
        'TransactionDescription' => 'client_code',
        'AuthorizationStatus'    => 'status',
        'BankReference'          => 'bank_payment_id',
    );

    /**
     * @param  array $input
     * @return array
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $featureFlag = "nb_" . $input['payment']['gateway'] . "_api_merchant_whitelisting";

        $variant = $this->app->razorx->getTreatment($input['payment']['merchant_id'], $featureFlag, $this->mode);

        $merchantId = $this->getMerchantId($variant, $input['merchant']->isTPVRequired());

        if ($merchantId === 'OSRAZORPAY' || $merchantId === 'OTRAZORPAY' || $merchantId === 'OTNRRAZORP')
        {
            $variant = self::newIntegration;
        }

        //checking verification_id and payment_id before adding to entity for excluding duplicates.
        $gatewayPayment = $this->checkTraceIdAndPaymentIdPresent($content, $input);

        if ($gatewayPayment === null)
        {
            $gatewayPayment = $this->createGatewayPaymentEntity($content);
        }

        $content['TraceNumber'] = $gatewayPayment['verification_id'];

        $content['checksum'] = $this->getHashOfArray($content);

        $contentString = implode('|', $content);

        $masterKey = $this->getEncryptionSecret();

        $encryptedString = $this->getRsaCrypter($masterKey)->encryptString($contentString);

        $traceContent = $content;

        if ($this->isPaymentTpvEnabled($gatewayPayment, $input['merchant']) === true)
        {
            if ($variant !== self::newIntegration)
            {
                unset($traceContent['TransactionDescription']);
            }
            else
            {
                unset($traceContent['FUP-2']);
            }
        }

        $content = [
            'msg'        => $encryptedString,
            'merchantId' => $merchantId,
        ];

        $request = $this->getStandardRequestArray($content);

        if ($this->mock === true)
        {
            $request['content']['msg'] = $request['content']['msg'] . '|' . $input['callbackUrl'];
        }

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_REQUEST, $traceContent);

        return $request;
    }
//
//    public function capture(array $input = array())
//    {
//        return parent::capture($input);
//    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'content' => $input['gateway'],
            ]);

        $masterKey = $this->getDecryptionSecret();

        $decryptedString = $this->getRsaCrypter($masterKey)->decryptString($input['gateway']['msg']);

        $content = $this->getDataFromResponse($decryptedString);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'parsed_content'    => $content,
            ]);

        $this->validateCallbackChecksum($content);

        // Unset date because format of date returned
        // is different than what we sent
        unset($content['DateTimeInGMT']);

        /** @var E $gatewayPayment */
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $gatewayPaymentId = (string) ($gatewayPayment->getVerificationId() ?: $gatewayPayment->getIntPaymentId());

        $this->assertPaymentId($gatewayPaymentId, $content['TraceNumber']);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($content['Amount'], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);

        $attrs['received'] = true;
        $attrs['status'] = $content['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $content['BankReference'];

        $gatewayPayment->fill($attrs);

        $gatewayPayment->saveOrFail();

        if ($attrs[Fields::STATUS] === Status::SUCCESS)
        {
            $response = $this->verifyCallback($input, $gatewayPayment);
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]
            );

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function preProcessServerCallback($input): array
    {
        $masterKey = $this->getDecryptionSecret();

        $decryptedString = $this->getRsaCrypter($masterKey)->decryptString($input['msg']);

        return explode('|', $decryptedString);
    }

    public function getPaymentIdFromServerCallback($input)
    {
        $paymentOrTraceId = $input[3];

        if (strlen($paymentOrTraceId) === 14)
        {
            return $paymentOrTraceId;
        }

        $nb = $this->app['repo']->netbanking->findByVerificationIdAndAction($paymentOrTraceId, Action::AUTHORIZE);

        if ($nb === null)
        {
            $this->app['config']->set('database.default', Mode::TEST);

            $nb = $this->app['repo']->netbanking->findByVerificationIdAndAction($paymentOrTraceId, Action::AUTHORIZE);
        }

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $paymentOrTraceId);
        }

        return $nb->getPaymentId();
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if ($content['AuthorizationStatus'] === 'Y')
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // From verified content put the bank payment id and
        // status
        $this->fillStatusAndBankPaymentId($input, $content);

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    /**
     * For verify, Set bank payment and status from
     * verified response.
     */
    protected function fillStatusAndBankPaymentId($input, $content)
    {
        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail(
            $input['payment']['id']);

        $attrs['received'] = true;
        $attrs['status'] = $content['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $content['BankReference'];

        $gatewayPayment->fill($attrs);

        $gatewayPayment->saveOrFail();
    }

    protected function validateCallbackChecksum($content)
    {
        $inputHash = $content['Checksum'];

        unset($content['Checksum']);

        //This is done because there is a extra '|' that has to be appended at then end of hash string.
        if ($this->action === Action::VERIFY)
        {
            $content['FieldToAppendExtraPipe'] = '';
        }

        $expectedHash = $this->getHashOfArray($content);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getDataFromResponse($data)
    {
        $content = explode('|', $data);

        $fields = $this->getFieldsForAction($this->action);

        /**
         * If Gateway returns data in invalid format,
         * then field count does not matches expected output format column count
         * throw Gateway unknown error exception
         */
        if (count($fields) !== count($content))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
        }

        $content = array_combine($fields, $content);

        return $content;
    }

    protected function getPaymentRequestData($input)
    {
        // Kotak asks for date in IST
        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        //Check for new integration whitelisted merchant
        $featureFlag = "nb_" . $input['payment']['gateway'] . "_api_merchant_whitelisting";

        $variant = $this->app->razorx->getTreatment($input['payment']['merchant_id'], $featureFlag, $this->mode);

        $merchantId = $this->getMerchantId($variant, $input['merchant']->isTPVRequired());

        if ($merchantId === 'OSRAZORPAY' || $merchantId === 'OTRAZORPAY' || $merchantId === 'OTNRRAZORP')
        {
            $variant = self::newIntegration;
        }

        if ($variant === self::newIntegration)
        {
            $data = array(
                'MessageCode'            => MessageCodes::AUTHORIZE,
                'DateTimeInGMT'          => $date,
                'MerchantId'             => $this->getMerchantId($variant, $input['merchant']->isTPVRequired()),
                'TraceNumber'            => $input['payment']['id'],
                'Amount'                 => $input['payment']['amount'] / 100,
                'TransactionDescription' => $this->getSubMerchantId($input['merchant']->isTPVRequired()),
                'FUP-1'                  => '',
                'FUP-2'                  => '',
                'FUP-3'                  => $input['merchant']['category'],
            );

            if ($input['merchant']->isTPVRequired() === true)
            {
                    $data['FUP-2']                  =  $input['order']['account_number'];
            }
        }

        else
        {
            $data = array(
                'MessageCode'            => MessageCodes::AUTHORIZE,
                'DateTimeInGMT'          => $date,
                'MerchantId'             => $input['terminal']['gateway_merchant_id'],
                'TraceNumber'            => $this->getTraceNumber($input),
                'Amount'                 => $input['payment']['amount'] / 100,
                'TransactionDescription' => $this->getDynamicMerchantName($input['merchant'], 50),
            );

            if ($this->mode === Mode::TEST)
            {
                $data['MerchantId'] = $this->getTestMerchantId();
            }

            // Change Content for Merchants with TPV Required
            if ($input['merchant']->isTPVRequired() === true)
            {
                $data['TransactionDescription'] = $input['order']['account_number'];

                if ($this->mode === Mode::TEST)
                {
                    $data['MerchantId'] = $this->getTestTpvMerchantId();
                }
            }
        }

        return $data;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $gatewayPayment = $verify->payment;

        $input = $verify->input;

        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        $contentArray = [
            'MessageCode'   => MessageCodes::VERIFY,
            'DateTimeInGMT' => $date,
            'MerchantId'    => $gatewayPayment['merchant_code'],
            'TraceNumber'   => $gatewayPayment[E::VERIFICATION_ID] ?: $gatewayPayment[E::INT_PAYMENT_ID],
            'Future1'       => '',
            'Future2'       => '',
        ];

        $msg = $this->getMessageStringWithHash($contentArray);

        $encryptedContent = $this->getCrypter()->encryptString($msg);

        $this->domainType = $this->mode . '_api_gw';

        $request = $this->getStandardRequestArray($encryptedContent, 'post');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'content' => $msg,
                'gateway' => 'netbanking_kotak'
            ]);

        $request['options']['verify'] = $this->getCaInfo();
        $request['headers']['Authorization'] = 'Bearer ' . $this->getToken();

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            ['response' => $response]);

        $content = $response->body;

        $encObj = $this->getCrypter();

        $content = $encObj->decryptString($content);

        $content = $this->getDataFromResponse($content);

        // adding checksum verification for verify
        $this->validateCallbackChecksum($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            ['responseContent' => $content]);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        if ($this->action === Action::AUTHORIZE)
        {
            $type = $this->mode . '_' . $type;
        }

        $type = strtoupper($type);

        return constant($ns . '\Url::' . $type);
    }

    public function getMessageStringWithHash($content)
    {
        $str = $this->getStringToHash($content, '|');

        $str = $str . '|';

        return $str . $this->getHashOfString($str);

    }

    protected function getTestMerchantId()
    {
        if ($this->action === Action::VERIFY)
        {
            return 'OSTECH';
        }
        else
        {
            return 'OSKOTAK';
        }
    }

    protected function getMerchantId($variant, $tpvReq)
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->getLiveMerchantId();
        }
        elseif ($this->mode === Mode::TEST)
        {
            return $this->input['terminal']['gateway_merchant_id'];
        }
    }

    protected function getSubMerchantId($tpvReq)
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->getLiveMerchantId2();
        }
        elseif ($this->mode === Mode::TEST)
        {
            if ($tpvReq === true)
            {
                return 'OTIND';
            }
            else
            {
                return 'IND15';
            }
        }
    }

    protected function getTestTpvMerchantId()
    {
        return 'OTTEST';
    }

    protected function getLiveSecret()
    {
        assertTrue ($this->mode === Mode::LIVE);

        return $this->config['live_hmac_hash_secret'];
    }

    protected function getHashOfString($str)
    {
        $key = $this->getSecret();

        return (hash_hmac('sha256', $str, $key));
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, '|');

        // Authorize and callback have the checksum in uppercase.
        if (($this->action === Action::AUTHORIZE) or
             ($this->action === Action::CALLBACK))
        {
            return strtoupper($this->getHashOfString($str));
        }

        return $this->getHashOfString($str);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //here the payment will be in created state and the callback has also returned a Success status so
        //marking apiSuccess as true
        $verify->apiSuccess = true;

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                [
                    'gateway'         => $this->gateway,
                    'verify_response' => $verify->verifyResponseContent,
                ]);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($verify->verifyResponseContent['Amount'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->assertPaymentId($verify->verifyResponseContent[Fields::BANK_REFERENCE_NO],
            $gatewayPayment[E::BANK_PAYMENT_ID]);

        return $verify->verifyResponseContent;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[Fields::AUTHORIZATION_STATUS]) === true) and
            ($content[Fields::AUTHORIZATION_STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/cainfo.pem';

        return $clientCertPath;
    }

    protected function getCrypter(): AESCrypto
    {
        $masterKey = $this->getEncryptionSecret();

        return new AESCrypto($masterKey);
    }

    public function getRsaCrypter($masterKey)
    {
        return new RSACrypto($masterKey);
    }

    protected function getEncryptionSecret()
    {
        $key = null;

        switch ($this->mode)
        {
            case Mode::TEST:
                switch ($this->action)
                {
                    case Action::AUTHORIZE:
                        $key = 'kotak_encrypt_secret';
                        break;
                    case Action::VERIFY:
                        $key = 'test_encrypt_hash_secret';
                        break;
                }
                break;
            case Mode::LIVE:
                switch ($this->action)
                {
                    case Action::AUTHORIZE:
                        $key = 'kotak_encrypt_secret';
                        break;
                    case Action::VERIFY:
                        $key = 'live_encrypt_hash_secret';
                        break;
                }
                break;
        }

        return $this->config[$key] ?? '';
    }

    protected function getDecryptionSecret()
    {
        return $this->config['kotak_decrypt_secret'];
    }

    protected function getTestSecret()
    {
        assert($this->mode === Mode::TEST);

        switch ($this->action)
        {
            case Action::VERIFY:
                return $this->config['test_verify_hash_secret'];

            default:
                return $this->config['test_hash_secret'];

        }
    }

    protected function getToken()
    {
        $content = $this->getTokenRequestContent();

        $request = $this->getStandardRequestArray($content, 'post', 'token');

        $request['headers']['Content-Type'] = 'application/x-www-form-urlencoded';

        $this->traceTokenData($request, TraceCode::GATEWAY_TOKEN_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->checkTokenResponse($response);

        return $responseArray['access_token'];
    }

    protected function checkTokenResponse($response)
    {
        $response = $this->jsonToArray($response->body);

        $this->traceTokenData($response, TraceCode::GATEWAY_TOKEN_RESPONSE);

        if (isset($response['error']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED,
                $response['error'],
                $response['error_description'],
                ['gateway' => $this->gateway]);
        }

        return $response;
    }

    public function traceTokenData($traceData, $traceCode)
    {
        unset($traceData['content']['client_id']);
        unset($traceData['content']['client_secret']);
        unset($traceData['access_token']);

        $this->trace->info(
            $traceCode,
            [
                'response' => $traceData,
                'gateway'  => $this->gateway,
            ]);
    }

    protected function getTokenRequestContent()
    {
        list($clientId, $clientSecret) = $this->getTokenClientIDSecret();

        $scope = $this->getTokenScope();

        $data = [
            'grant_type'     => 'client_credentials',
            'client_id'      => $clientId,
            'client_secret'  => $clientSecret,
            'scope'          => $scope,
        ];

        return $data;
    }

    protected function getTokenClientIDSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return [$this->config['test_token_client_id'], $this->config['test_token_client_secret']];
        }

        return [$this->config['live_token_client_id'], $this->config['live_token_client_secret']];
    }

    protected function getTokenScope()
    {
        if ($this->mode === Mode::TEST)
        {
            return Fields::TEST_SCOPE;
        }

        return Fields::LIVE_SCOPE;
    }

    protected function getTraceNumber($input)
    {
        $txnDesc = time() . random_integer(5);

        if ($input['payment']['merchant_id'] === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $txnDesc = substr($input['payment']['description'], 0, 16);
        }

        return $txnDesc;
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::SUCCESS))
        {
            return true;
        }

        $attrs = [
            Base\Entity::STATUS => Status::SUCCESS
        ];

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function checkTraceIdAndPaymentIdPresent($content, $input)
    {
        $gatewayResponse = $this->repo->findByVerificationIdOrPaymentIdAndAction($content['TraceNumber'], Action::AUTHORIZE, $input['payment']['id']);

        if ($gatewayResponse !== null)
        {
            if ($gatewayResponse['payment_id'] === $input['payment']['id'])
            {
                return $gatewayResponse;
            }
            elseif ($gatewayResponse['verification_id'] === $content['TraceNumber'])
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    [
                        'gateway'    => $this->gateway,
                        'payment_id' => $input['payment']['id'],
                        'trace_id'   => $gatewayResponse['verification_id'],
                    ]);
            }
        }
        return $gatewayResponse;
    }
}
