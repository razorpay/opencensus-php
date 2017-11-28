<?php

namespace RZP\Gateway\Aeps\Icici;

use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Terminal;
use RZP\Gateway\Aeps\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = 'aeps_icici';

    const ACQUIRER = 'icici';

    const CACHE_TTL = 60;

    const TERMINAL_ID = 'terminal_id';

    const FAILED = 'failed';
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

            $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
                'gateway' => $this->gateway,
                'response' => $response
            ]);

            $parsedResponse = $this->parseResponse($response);

            $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
                'gateway' => $this->gateway,
                'response' => $parsedResponse
            ]);

            $paymentStatus = $this->updateGatewayPaymentAndGetStatus($gatewayPayment, $parsedResponse);

            if ($paymentStatus !== SELF::SUCCESS)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
            }
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $reversalRequestData = $this->getReversalData($requestData);

            $reversalRequestXmlData = $this->getRequestXml($reversalRequestData);

            try
            {
                $reversalResponse = $this->sendReversalRequest($reversalRequestXmlData);

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

        $iv = $this->getIV();

        $encryptor = (new Encryptor(2, $iv));

        $sKey = $encryptor->generateSkey();

        $data = [
            'account-provider'    => '1',
            'mobile'              => '9999999999', // TDOO Read from config
            'payer-va'            => 'razorpay1@icici', // TDOO Read from config
            'amount'              => '100.00',  // TDOO Read from input
            'note'                => 'test',
            'device-id'           => '107824107824107824107824',
            'seq-no'              => 'ef1e92b4a01d4618a0eca5fdecc37ff23f3', // TODO generate random no
            'channel-code'        => 'EAZYPAY',
            'profile-id'          => '723',
            'account-type'        => 'Saving',
            'ifsc'                => '',
            'account-number'      => '',
            'mpin'                => '',
            'pre-approved'        => 'A',
            'use-default-acc'     => 'D',
            'default-debit'       => 'N',
            'default-credit'      => 'N',
            'global-address-type' => 'AADHAR',
            'payee-aadhar'        => '123456789012', // TODO read form gateway input
            'payee-iin'           => '',
            'payee-name'          => '',
            'mcc'                 => '5411',
            'merchant-type'       => 'ENTITY',
        ];

        $data = $encryptor->encryptUsingSessionKey($data, $sKey);

        $encryptedKey = $encryptor($sKey, $this->mode, 'refund');

        $contents = [
            'requestId'            => '',
            'service'              => 'UPI',
            'encryptedKey'         => $encryptedKey,
            'oaepHashingAlgorithm' => 'NONE',
            'iv'                   => $iv,
            'encryptedData'        => $data,
            'clientInfo'           => '',
            'optionalParam'        => '',
        ];

        $request = [
            'url'     =>  Url::TEST_REFUND_URL,
            'method'  => 'POST',
            'content' => json_encode($contents),
        ];

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [$response->body]);

        //TODO do stuff after this is done
    }

    protected function getIV()
    {
        return '';
    }

    protected function setEncryptedFingerPrintDataInCache($input)
    {
        $key = $this->getCacheKey($input['payment']['id']);

        Cache::store($this->secureCacheDriver)->put($key, $input, self::CACHE_TTL);
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

    protected function getPaymentResponseData($gatewayPayment)
    {
        //TODO : Write Response Parser
    }

    protected function updateGatewayPaymentForReversal($gatewayPayment, $response)
    {
        if (isset($response[ResponseConstants::STATUS]) === true)
        {
            if ($response[ResponseConstants::STATUS] === '00')
            {
                $gatewayPayment->setReversed(1);
            }
            else
            {
                $gatewayPayment->setReversed(0);

                $gatewayPayment->setReversalErrorCode($response[ResponseConstants::STATUS]);

                if (isset($response[ResponseConstants::DESCRIPTION]) === true)
                {
                    $gatewayPayment->setReversalErrorDescription($response[ResponseConstants::DESCRIPTION]);
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

        if (isset($response[ResponseConstants::RRN]) === true)
        {
            $gatewayPayment->setRrn($response[ResponseConstants::RRN]);
        }

        if (isset($response[ResponseConstants::STATUS]) === true)
        {
            if ($response[ResponseConstants::STATUS] === '00')
            {
                $gatewayPayment->setReceived(1);

                $paymentStatus = self::SUCCESS;
            }
            else
            {
                $gatewayPayment->setReceived(0);
                $gatewayPayment->setErrorCode($response[ResponseConstants::STATUS]);

                if (isset($response[ResponseConstants::DESCRIPTION]) === true)
                {
                    $gatewayPayment->setErrorDescription($response[ResponseConstants::DESCRIPTION]);
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

    protected function sendReversalRequest($reversalRequestXmlData)
    {
        $response = $this->sendRequest($reversalRequestXmlData);
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

        $extraBlock = '001344' . $input['aadhaar']['session_key'] . '002008' . $input['aadhaar']['cert_expiry'] . '003064' . $input['aadhaar']['hmac'];

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

        $xmlStringPrefix = "\n<isomsg direction=\"incoming\">\n<!-- org.jpos.iso.packager.GenericPackager[cfg/iso87binary-sarvatra.xml] -->\n<header>00000000</header>\n";

        $xmlStringPostfix = "</isomsg>\n";

        $xmlString .= $xmlStringPrefix;

        foreach ($data as $key => $value)
        {
            $xmlString .= '<field id="' . $key . '" value="' . $value . '"/>' . "\n";
        }

        $xmlString .= $xmlStringPostfix;

        return $xmlString;
    }

    protected function createGatewayPaymentEntity($input, $requestData)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        $gatewayPayment->setAadhaarNumber($input['aadhaar']['number']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->setAcquirer($input['terminal']['gateway_acquirer']);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setCounter($requestData[RequestConstants::COUNTER]);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }
}
