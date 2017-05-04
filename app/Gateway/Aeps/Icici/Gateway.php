<?php

namespace RZP\Gateway\Aeps\Icici;

use Cache;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Terminal;

class Gateway extends Base\Gateway
{
    protected $gateway = 'aeps_icici';

    const ACQUIRER = 'icici';

    const CACHE_TTL = 60;

    const TERMINAL_ID = 'terminal_id';

    public function __construct()
    {
        parent::__construct();

        $this->secureCacheDriver = Config::get('cache.secure_default');

        $this->cache = Cache::getFacadeRoot();
    }

    public function authorize(array $input)
    {
        if ((isset($input['encrypted']) === true) and
            ($input['encrypted'] === false))
        {
            $encryptor = (new Encryptor);

            $encryptor->encryptInput($input);

            unset($input['encrypted']);
        }

        $gatewayPayment = $this->createGatewayPaymentEntity($input);

        // This need to be done for reversal request,
        // As reversal is done in the same flow skipping for now
        //$this->setEncryptedFingerPrintDataInCache($input);

        $requestData = $this->getRequestData($input);

        $requestXmlData = $this->getRequestXml($requestData);

        try
        {
            $response = $this->sendRequest($requestXmlData);

            $parsedResponse = $this->parseResponse($response);

            $this->updateGatewayPayment($parsedResponse);
        }
        catch (\Exception $e)
        {
            //TODO catch Timeout exception, instead of generic Exception
            // Timeout should be 90 secs
            $reversalRequestData = $this->getReversalData($requestData);

            $reversalRequestXmlData = $this->getRequestXml($reversalRequestData);

            try
            {
                $reversalResponse = $this->sendReversalRequest($reversalRequestXmlData);

                $parsedReversalResponse = $this->parseResponse($reversalResponse);

                $this->updateGatewayPayment($parsedReversalResponse);
            }
            catch (\Exception $e)
            {
                //TODO trace and silently exit

            }
        }
        finally
        {
            //As reversal is done in same thread, cache can be ignored
            //$this->deleteEncryptedFingerPrintDataFromCache($input);
        }

        return $this->getPaymentResponseData($gatewayPayment);
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

    protected function createGatewayPaymentEntity($input)
    {
        //TODO store aadhaar number
    }

    protected function parseResponse($response)
    {
        $responseArray = [];

        if ($response === null)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR);
        }

        $fieldCount = $response->count();

        foreach (range(0, $fieldCount - 1) as $index)
        {
            foreach ($response->field[$index]->attributes() as $a => $b)
            {
                if ($a === 'id')
                {
                    $key = $b;
                }
                else if ($a === 'value')
                {
                    $value = $b;
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

    protected function updateGatewayPayment($response)
    {
        //TODO : FIX IT
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

        $date = Carbon::now('Asia/Kolkata')->format('Y-m-d\TH:i:s');

        // TODO fill field 60 n 127
        $data = [
            '0'   => $msgType,
            '2'   => $bankIin . '0' . $input['aadhaar_number'],
            '3'   => '421000',
            '4'   => $amount,
            '11'  => $counter,
            '22'  => '019',
            '24'  => '001',
            '25'  => '05',
            '36'  => 'WDLS C1||,,,,,,',
            '41'  => $terminalId,
            '42'  => '       RAZORPAY',
            '60'  => $input['aadhaar_fingerprint'],
            '125' => $transactionType,
            '126' => '"001009nnnyFMRnn008001X401019' . $date . '402001F403001Y404006607580412008' . $terminalId,
            '127' => '001344' . $input['aadhaar_session_key'] . '002008' . $input['aadhaar_cert_expiry'] . '003064' . $input['aadhaar_hmac'],
        ];

        return $data;
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

        return $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];
    }

    protected function getCounter()
    {
        $cacheKey = 'AEPS_COUNTER_' . Carbon::now('Asia/Kolkata')->format('Ymd');

        $counter = $this->cache->increment($cacheKey);

        return $counter;
    }

    protected function getRequestXml($data)
    {
        $xmlString = '';

        $xmlStringPrefix = '<isomsg direction="incoming"><header>00000000</header>';

        $xmlStringPostfix = '</isomsg>';

        foreach ($data as $key => $value)
        {
             $xmlString .= '<field id="' . $key . '" value="' . $value . '"/>';
        }

        $xmlString .= $xmlStringPostfix;

        return $xmlString;
    }
}
