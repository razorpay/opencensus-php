<?php

namespace RZP\Gateway\Aeps\Icici;

class Gateway extends Base\Gateway
{
    protected $gateway = 'aeps_icici';

    const ACQUIREE = 'icici';

    public function authorize(array $input)
    {
        if ((isset($input['encrypted']) === true) and
            ($input['encrypted'] === false))
        {
            $encryptor = (new Encryptor);

            $encryptor->encryptInput($input);

            unset($input['encrypted']);
        }

        // This need to be done for reversal request,
        $this->setEncryptedFingerPrintDataInRedis($input);

        $requestXmlData = $this->getRequestXml($input);

        try
        {
            $response = $this->sendRequest($requestXmlData);

             $this->updateGatewayPayment($response);
        }
        catch (\Exception $e)
        {
            //catch Timeout exception, instead of generic Exception
            // Timeout should be 90 secs
            $reversal = true;

            $reversalRequestXmlData = $this->getRequestXml($input, $reversal);

            $reversalResponse = $this->sendReversalRequest($reversalRequestXmlData);

            $this->updateGatewayPayment($reversalResponse);
        }
        finally
        {
            $this->deleteEncryptedFingerPrintDataInRedis($input);
        }

        return $this->getPaymentResponseData($gatewayPayment);
    }

    protected function getPaymentResponseData($gatewayPayment)
    {

    }

    protected function updateGatewayPayment($response)
    {

    }

    protected function sendReversalRequest($reversalRequestXmlData)
    {
        try
        {
            $response = $this->sendRequest($reversalRequestXmlData);
        }
        catch (\Exception $e)
        {
            // Trace as reversal has also failed, and think of a better way to handle this
        }
    }
}
