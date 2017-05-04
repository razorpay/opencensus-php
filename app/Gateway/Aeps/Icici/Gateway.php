<?php

namespace RZP\Gateway\Aeps\Icici;

use Cache;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'aeps_icici';

    const ACQUIRER = 'icici';

    const CACHE_TTL = 60;

    public function __construct()
    {
        parent::__construct();

        $this->secureCacheDriver = Config::get('cache.secure_default');
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

        $requestXmlData = $this->getRequestXml($input);

        try
        {
            $response = $this->sendRequest($requestXmlData);

            $parsedResponse = $this->parseResponse($response);

            $this->updateGatewayPayment($parsedResponse);
        }
        catch (\Exception $e)
        {
            //catch Timeout exception, instead of generic Exception
            // Timeout should be 90 secs
            $reversal = true;

            $reversalRequestXmlData = $this->getRequestXml($input, $reversal);

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

    protected function getRequestData($input, $reversal)
    {
        $msgType = '0100';

        if ($reversal === true)
        {
            $msgType = '0400';
        }

        $bankIin = '607202';

        $amount = '000000001000';

        $randomNo = '000013';

        $terminalId = '77777777';

        $date = '2017-04-09T11:11:10';

        // FIX IT
        $input['aadhaar_no'] = '123456789012';
        // TODO fill field 60 n 127
        $data = [
            '0'   => $msgType,
            '2'   => $bankIin . '0' . $input['aadhaar_no'],
            '3'   => '421000',
            '4'   => $amount,
            '11'  => $randomNo,
            '22'  => "019",
            '24'  => '001',
            '25'  => '05',
            '36'  => 'WDLS C1||,,,,,,',
            '41'  => $terminalId,
            '42'  => '       RAZORPAY',
            '60'  => '',
            '125' => 'OFFUS.APAY',
            '126' => '"001009nnnyFMRnn008001X401019' . $date . '402001F403001Y40400660758041200' . $terminalId,
            '127' => '',
        ];

        return $data;
    }

    protected function getRequestXml($input, $reversal = false)
    {
        $xmlString = '';

        $xmlStringPrefix = '<isomsg direction="incoming"><header>00000000</header>';

        $xmlStringPostfix = '</isomsg>';

        $data = $this->getRequestData($input, $reversal);

        foreach ($data as $key => $value)
        {
             $xmlString .= '<field id="' . $key . '" value="' . $value . '"/>';
        }

        $xmlString .= $xmlStringPostfix;

        return $xmlString;
    }
}
