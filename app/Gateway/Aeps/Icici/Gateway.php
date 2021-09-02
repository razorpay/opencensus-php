<?php

namespace RZP\Gateway\Aeps\Icici;

use Cache;
use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Aeps\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'aeps_icici';

    const ACQUIRER = 'icici';

    const CACHE_TTL_IN_MINS = 60;

    const TERMINAL_ID = 'terminal_id';

    const FAILED  = 'failed';
    const SUCCESS = 'success';

    public function __construct()
    {
        parent::__construct();

        $this->cache = Cache::getFacadeRoot();
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ((isset($input['aadhaar']['encrypted']) === true) and
            ($input['aadhaar']['encrypted'] === false))
        {
            $encryptor = (new Encryptor);

            $encryptor->encryptInput($input, $this->mode);
        }

        // This need to be done for reversal request via cron,
        // As reversal is done in the same flow skipping for now
        // $this->setEncryptedFingerPrintDataInCache($input);

        $requestData = $this->getRequestData($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($input, $requestData);

        $this->traceRequest($requestData);

        $requestXmlData = $this->getRequestXml($requestData);

        try
        {
            $response = $this->sendRequest($requestXmlData);

            $this->trace->info(
                TraceCode::GATEWAY_RESPONSE, [
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                    'response'   => $response,
                ]
            );

            $parsedResponse = $this->parseResponse($response);

            $this->trace->info(
                TraceCode::GATEWAY_RESPONSE,
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                    'response'   => $parsedResponse,
                ]
            );

            $paymentStatus = $this->updateGatewayPaymentAndGetStatus($gatewayPayment, $parsedResponse);

            if ($paymentStatus !== SELF::SUCCESS)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    $parsedResponse[ResponseConstants::AUTH_RESPONSE_CODE]
                );
            }
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $reversalRequestData = $this->getReversalData($requestData);

            $reversalRequestXmlData = $this->getRequestXml($reversalRequestData);

            try
            {
                $reversalResponse = $this->sendRequest($reversalRequestXmlData);

                $parsedReversalResponse = $this->parseResponse($reversalResponse);

                $paymentStatus = $this->updateGatewayPaymentAndGetStatus($gatewayPayment, $parsedReversalResponse);

                // After reverse is complete, We have to throw exception as
                // paymnet failed overall
                if ($paymentStatus !== self::SUCCESS)
                {
                    $this->trace->error(
                        TraceCode::PAYMENT_REVERSE_FAILURE);
                }

                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);

            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::PAYMENT_REVERSE_FAILURE,
                    $e->getMessage());
            }
        }
        finally
        {
            //As reversal is done in same thread, cache can be ignored
            //$this->deleteEncryptedFingerPrintDataFromCache($input);
        }

        //TODO IN future we may want to return RRN number
        //return $this->getPaymentResponseData($gatewayPayment);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'   => $this->gateway,
                'refund_id' => $input['refund']['id'],
                'content'   => $response->body,
            ]
        );

        $responseData = json_decode($response->body, true);

        $responseData = $this->getRefundDecryptedData($responseData);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'   => $this->gateway,
                'refund_id' => $input['refund']['id'],
                'decrypted' => true,
                'response'  => $responseData,
            ]
        );

        // Store  refund response
        $this->updateRefundResponse($gatewayPayment, $responseData);

        if ((isset($responseData[ResponseConstants::REFUND_RESPONSE]) === false) or
            ($responseData[ResponseConstants::REFUND_RESPONSE] !== Status::REFUND_STATUS_SUCCESS))
        {
            // Can't validate amount here, since amount does not exist in response

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $responseData[ResponseConstants::REFUND_RESPONSE] ?? '',
                $responseData[ResponseConstants::REFUND_MESSAGE] ?? ''
            );
        }
    }

    protected function getRefundRequest(array $input): array
    {
        $encryptor = $this->getEncryptor();

        $sKey = $encryptor->generateSkey();

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $amount = number_format($input['refund']['amount'] / 100, 2, '.', '');

        $data = [
            RequestConstants::REFUND_DATA_ACCOUNT_PROVIDER    => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_account_provider_test'] :
                                                                    $this->config['refund_account_provider_live']
                                                                 ),
            RequestConstants::REFUND_DATA_MOBILE              => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_payer_mobile_test'] :
                                                                    $this->config['refund_payer_mobile_live']
                                                                 ),
            RequestConstants::REFUND_DATA_PAYER_VA            => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_payer_vpa_test'] :
                                                                    $this->config['refund_payer_vpa_live']
                                                                 ),
            RequestConstants::REFUND_DATA_AMOUNT              => $amount,
            RequestConstants::REFUND_DATA_NOTE                => $input['payment']['id'],
            RequestConstants::REFUND_DATA_DEVICE_ID           => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_device_id_test'] :
                                                                    $this->config['refund_device_id_live']
                                                                 ),
            RequestConstants::REFUND_DATA_SEQ_NO              => strtolower('ici' . upi_uuid(false)),
            RequestConstants::REFUND_DATA_CHANNEL_CODE        => $this->config['channel_code'],
            RequestConstants::REFUND_DATA_PROFILE_ID          => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_profile_id_test'] :
                                                                    $this->config['refund_profile_id_live']
                                                                 ),
            RequestConstants::REFUND_DATA_ACCOUNT_TYPE        => Constants::REFUND_DATA_ACCOUNT_TYPE,
            RequestConstants::REFUND_DATA_PRE_APPROVED        => Constants::REFUND_DATA_PRE_APPROVED,
            RequestConstants::REFUND_DATA_USE_DEFAULT_ACC     => Constants::REFUND_DATA_USE_DEFAULT_ACC,
            RequestConstants::REFUND_DATA_DEFAULT_DEBIT       => Constants::REFUND_DATA_DEFAULT_DEBIT,
            RequestConstants::REFUND_DATA_DEFAULT_CREDIT      => Constants::REFUND_DATA_DEFAULT_CREDIT,
            RequestConstants::REFUND_DATA_GLOBAL_ADDRESS_TYPE => Constants::REFUND_DATA_GLOBAL_ADDRESS_TYPE,
            RequestConstants::REFUND_DATA_PAYEE_AADHAR        => $gatewayEntity[Base\Entity::AADHAAR_NUMBER],
            RequestConstants::REFUND_DATA_PAYEE_IIN           => '',
            RequestConstants::REFUND_DATA_PAYEE_NAME          => Constants::REFUND_DATA_PAYEE_NAME,
            RequestConstants::REFUND_DATA_MCC                 => (
                                                                    $this->mode === Mode::TEST ?
                                                                    $this->config['refund_mcc_test'] :
                                                                    $this->config['refund_mcc_live']
                                                                 ),
            RequestConstants::REFUND_DATA_MERCHANT_TYPE       => Constants::REFUND_DATA_MERCHANT_TYPE,
        ];

        $data = json_encode($data);

        $encryptedData = $encryptor->encryptUsingSessionKey($data, $sKey);

        $encryptedKey = $encryptor->encryptSessionKey($sKey, $this->mode, 'refund');

        $content = [
            RequestConstants::REFUND_REQUEST_REQUESTID            => $input['refund']['id'],
            RequestConstants::REFUND_REQUEST_SERVICE              => 'UPI',
            RequestConstants::REFUND_REQUEST_ENCRYPTEDKEY         => $encryptedKey,
            RequestConstants::REFUND_REQUEST_OAEPHASHINGALGORITHM => 'NONE',
            RequestConstants::REFUND_REQUEST_IV                   => base64_encode($this->getIv()),
            RequestConstants::REFUND_REQUEST_ENCRYPTEDDATA        => $encryptedData,
            RequestConstants::REFUND_REQUEST_CLIENTINFO           => '',
            RequestConstants::REFUND_REQUEST_OPTIONALPARAM        => '',
        ];

        $content = json_encode($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'gateway'        => $this->gateway,
                'payment_id'     => $input['payment']['id'],
                'data'           => $data,
                'encrypted_data' => $encryptedData,
                'content'        => $content,
                'session_key'    => $sKey,
            ]
        );

        $request = [
            'url'     => ($this->mode === Mode::TEST ? Url::TEST_REFUND_URL : Url::LIVE_REFUND_URL),
            'method'  => 'POST',
            'content' => $content,
            'headers' => [
                RequestConstants::REFUND_REQUEST_API_KEY => (
                                                                $this->mode === Mode::TEST ?
                                                                $this->config['refund_api_key_test'] :
                                                                $this->config['refund_api_key_live']
                                                             ),
                'Content-Type' => 'application/json'
            ]
        ];

        return $request;
    }

    protected function getRefundDecryptedData(array $data): array
    {
        $encryptor = $this->getEncryptor();

        $sessionKey = $encryptor->decryptSessionKey($data[ResponseConstants::REFUND_RESPONSE_ENCRYPTEDKEY]);

        $data = $encryptor->decryptUsingSessionKey(
            $data[ResponseConstants::REFUND_RESPONSE_ENCRYPTEDDATA],
            $sessionKey
        );

        $data = mb_convert_encoding($data, 'Windows-1252', 'UTF-8');

        // Strip out the random 16 characters at the beginning
        return json_decode($data, true);
    }

    // This gets overridden in Mock gateway
    protected function getEncryptor(): Encryptor
    {
        $encryptor = new Encryptor(AES::MODE_CBC, $this->getIv());

        $encryptor->setPublicKey($this->getRefundPublicKey());

        $encryptor->setPrivateKey($this->getRefundPrivateKey());

        return $encryptor;
    }

    public function getIv()
    {
        return '';
    }

    protected function setEncryptedFingerPrintDataInCache($input)
    {
        $key = $this->getCacheKey($input['payment']['id']);

        // Multiplying by 60 since cache put() expect ttl in seconds
        Cache::store($this->secureCacheDriver)->put($key, $input, self::CACHE_TTL_IN_MINS * 60);
    }

    protected function deleteEncryptedFingerPrintDataFromCache($input)
    {
        $key = $this->getCacheKey($input['payment']['id']);

        return Cache::store($this->secureCacheDriver)->get($key) ?: [];
    }

    protected function parseResponse($response)
    {
        $responseArray = [];

        if ($response === null)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR);
        }

        $xmlResponse = simplexml_load_string($response);

        $fieldCount = $xmlResponse->count();

        foreach (range(0, $fieldCount - 2) as $index)
        {
            foreach ($xmlResponse->field[$index]->attributes() as $a => $b)
            {
                if ($a === 'id')
                {
                    $key = $b->__toString();
                }
                else if ($a === 'value')
                {
                    $value = $b->__toString();
                }
            }

            $responseArray[$key] = $value;
        }

        return $responseArray;
    }

    protected function updateRefundResponse($gatewayPayment, $response)
    {
        $input = [
            Base\Entity::RRN               => $response[ResponseConstants::REFUND_BANKRRN],
            Base\Entity::RECEIVED          => 1,
            Base\Entity::ERROR_CODE        => $response[ResponseConstants::REFUND_RESPONSE],
            Base\Entity::ERROR_DESCRIPTION => $response[ResponseConstants::REFUND_MESSAGE],
        ];

        $gatewayPayment->fill($input);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getPaymentResponseData($gatewayPayment)
    {
        //TODO : Write Response Parser
    }

    protected function updateGatewayPaymentForReversal($gatewayPayment, $response)
    {
        if (isset($response[ResponseConstants::AUTH_RESPONSE_CODE]) === true)
        {
            if ($response[ResponseConstants::AUTH_RESPONSE_CODE] === '00')
            {
                $gatewayPayment->setReversed(1);
            }
            else
            {
                $gatewayPayment->setReversed(0);

                $gatewayPayment->setReversalErrorCode($response[ResponseConstants::AUTH_RESPONSE_CODE]);

                if (isset($response[ResponseConstants::AUTH_ADDITIONAL_DATA]) === true)
                {
                    $gatewayPayment->setReversalErrorDescription($response[ResponseConstants::AUTH_ADDITIONAL_DATA]);
                }
            }
        }
        else
        {
            $gatewayPayment->setReversed(0);
        }
    }

    protected function updateGatewayPaymentAndGetStatus($gatewayPayment, $response)
    {
        $paymentStatus = self::FAILED;

        $gatewayPayment->setReceived(1);

        if (isset($response[ResponseConstants::AUTH_RRN]) === true)
        {
            $gatewayPayment->setRrn($response[ResponseConstants::AUTH_RRN]);
        }

        if (isset($response[ResponseConstants::AUTH_RESPONSE_CODE]) === true)
        {
            if ($response[ResponseConstants::AUTH_RESPONSE_CODE] === '00')
            {
                $paymentStatus = self::SUCCESS;
            }
            else
            {
                $gatewayPayment->setErrorCode($response[ResponseConstants::AUTH_RESPONSE_CODE]);

                if (isset($response[ResponseConstants::AUTH_ADDITIONAL_DATA]) === true)
                {
                    $gatewayPayment->setErrorDescription($response[ResponseConstants::AUTH_ADDITIONAL_DATA]);
                }
            }
        }
        else
        {
            $gatewayPayment->setReceived(0);
        }

        $this->repo->saveOrFail($gatewayPayment);

        return $paymentStatus;
    }

    protected function sendRequest($requestXmlData)
    {
        $socket = new Socket;

        $socket->sendData($requestXmlData);

        return $socket->receiveData();
    }

    protected function getRequestData($input)
    {
        $msgType = RequestConstants::REQUEST_MSG_TYPE;

        $counter = $this->getCounter();

        $transactionType = RequestConstants::OFFUS;

        if ($input['payment']['bank'] === IFSC::ICIC)
        {
            $transactionType = RequestConstants::ONUS;
        }

        $bankIin = BankIin::$map[$input['payment']['bank']];

        $amount = str_pad($input['payment']['amount'], 12, '0', STR_PAD_LEFT);

        $terminalId = $this->getTerminalId();

        $date = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

        $extraBlock = '001344'
                    . $input['aadhaar']['session_key']
                    . '002008'
                    . $input['aadhaar']['cert_expiry']
                    . '003064'
                    . $input['aadhaar']['hmac'];

        $fpInfo = '001009nnnyFMRnn008001X401019' . $date . '402001F403001Y404006607580412008' . $terminalId;

        $data = [
            RequestConstants::MSG_TYPE    => $msgType,
            RequestConstants::ACC_NO      => $bankIin . '0' . $input['aadhaar']['number'],
            RequestConstants::REQ_TYPE    => '421000',
            RequestConstants::AMOUNT      => $amount,
            RequestConstants::COUNTER     => $counter,
            RequestConstants::F22         => '019',
            RequestConstants::F24         => '001',
            RequestConstants::F25         => '05',
            RequestConstants::F36         => 'WDLS C1||,,,,,,',
            RequestConstants::TERMINAL_ID => $terminalId,
            RequestConstants::F42         => '       RAZORPAY',
            RequestConstants::PID_BLOCK   => $input['aadhaar']['fingerprint'],
            RequestConstants::TRANS_TYPE  => $transactionType,
            RequestConstants::FP_INFO     => $fpInfo,
            RequestConstants::EXTRA_BLOCK => $extraBlock,
        ];

        return $data;
    }

    protected function traceRequest($data)
    {
        unset($data[RequestConstants::PID_BLOCK]);

        unset($data[RequestConstants::EXTRA_BLOCK]);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, [
                'gateway' => $this->gateway,
                'request' => $data
            ]);
    }

    protected function getReversalData($data)
    {
        $data['0'] = RequestConstants::REVERSAL_MSG_TYPE;
    }

    protected function getTerminalId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::TERMINAL_ID];
        }

        return $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
    }

    protected function getCounter()
    {
        $cacheKey = 'AEPS_COUNTER_' . Carbon::now(Timezone::IST)->format('Ymd');

        $counter = $this->cache->increment($cacheKey);

        return $counter;
    }

    protected function getRequestXml($data)
    {
        $xmlString = '';

        $xmlStringPrefix = "\n<isomsg direction=\"incoming\">"
                         . "\n<!-- org.jpos.iso.packager.GenericPackager[cfg/iso87binary-sarvatra.xml] -->"
                         . "\n<header>00000000</header>\n";

        $xmlStringPostfix = "</isomsg>\n";

        $xmlString .= $xmlStringPrefix;

        foreach ($data as $key => $value)
        {
            $xmlString .= '<field id="' . $key . '" value="' . $value . '"/>' . "\n";
        }

        $xmlString .= $xmlStringPostfix;

        return $xmlString;
    }

    protected function createGatewayPaymentEntity(array $input, array $requestData = [])
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        if (isset($input['aadhaar']) === true)
        {
            $gatewayPayment->setAadhaarNumber($input['aadhaar']['number']);
        }

        if ($this->action === 'refund')
        {
            $gatewayPayment->setAmount($input['refund']['amount']);
        }
        else
        {
            $gatewayPayment->setAmount($input['payment']['amount']);
        }

        $gatewayPayment->setAcquirer($input['terminal']['gateway_acquirer']);

        $gatewayPayment->setAction($this->action);

        // This is used in auth
        if (isset($requestData[RequestConstants::COUNTER]) === true)
        {
            $gatewayPayment->setCounter($requestData[RequestConstants::COUNTER]);
        }

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getRefundPublicKey()
    {
        $key = $this->config['refund_live_public_key'];

        if ($this->mode === Mode::TEST)
        {
            $key = $this->config['refund_test_public_key'];
        }

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }

    protected function getRefundPrivateKey()
    {
        if ($this->mode === Mode::TEST)
        {
            $key = $this->config['refund_test_private_key'];
        }
        else
        {
            $key = $this->config['refund_live_private_key'];
        }

        return trim(str_replace('\n', "\n", $key));
    }
}
