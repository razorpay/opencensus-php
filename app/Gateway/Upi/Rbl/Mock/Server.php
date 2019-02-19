<?php

namespace RZP\Gateway\Upi\Rbl\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Rbl\Action;
use RZP\Gateway\Upi\Rbl\Fields;
use RZP\Http\Response\Response;
use RZP\Gateway\Upi\Rbl\Status;

class Server extends Mock\Server
{
    public function sessionToken($input)
    {
        parent::setAction(Action::SESSION_TOKEN);

        parent::setInput($input);

        $content = $this->xmlToArray($input);

        $this->validateActionInput($content);

        $timeout = Carbon::now(Timezone::IST)->addHours(1)->format('m/d/Y H:i:s A');

        $response = [
            Fields::CHANNEL_PARTNER_LOGIN_RESPONSE => [
                Fields::SESSION_TOKEN => str_random(40),
                Fields::STATUS => 1,
                Fields::TIMEOUT => $timeout,
            ]
        ];

        $this->content($response, $this->action);

        $responseXml = $this->arrayToXml($response);

        return $this->makeXmlResponse($responseXml);
    }

    public function generateAuthToken($input)
    {
        parent::setAction(Action::GENERATE_AUTH_TOKEN);

        parent::setInput($input);

        $content = $this->xmlToArray($input);

        $this->validateActionInput($content);

        $response = [
            Fields::GENERATE_AUTH_TOKEN_RESPONSE => [
                Fields::STATUS => '1',
                Fields::TOKEN  => str_random(35),
            ]
        ];

        $this->content($response, $this->action);

        $responseXml = $this->arrayToXml($response);

        return $this->makeXmlResponse($responseXml);
    }

    public function getTransactionId($input)
    {
        parent::setAction(Action::GET_TRANSACTION_ID);

        parent::setInput($input);

        $content = $this->xmlToArray($input);

        $this->validateActionInput($content);

        $response = [
            Fields::GET_TRANSACTION_ID_RESPONSE => [
                Fields::STATUS => '1',
                Fields::DESCRIPTION  => str_random(50),
            ]
        ];

        $this->content($response, $this->action);

        $responseXml = $this->arrayToXml($response);

        return $this->makeXmlResponse($responseXml);
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $content = $this->xmlToArray($input);

        $this->validateActionInput($content);

        $response = [
            Fields::COLLECT_RESPONSE => [
                Fields::STATUS => '1',
                Fields::RESULT  => Status::SUCCESS, // need to check if this field is Status or Description
                Fields::GATEWAY_TRANSACTION_ID => $content[Fields::GATEWAY_TRANSACTION_ID],
            ]
        ];

        $this->content($response, $this->action);

        $responseXml = $this->arrayToXml($response);

        return $this->makeXmlResponse($responseXml);
    }

    public function verify($input)
    {
        parent::verify($input);

        $content = $this->xmlToArray($input);

        $this->validateActionInput($content);

        $response = [
            Fields::SEARCH_REQUEST => [
                Fields::STATUS                => '1',
                Fields::DESCRIPTION           => Status::SUCCESS,
                // need to check if this field is Status or Description
                Fields::PAYER_VPA             => 'random@vpa',
                Fields::PAYER_MOBILE          => '91888888888',
                Fields::AMOUNT                => '',
                Fields::PAYER_ACCOUNT_NUMBER  => '12345678910',
                Fields::PAYER_IFSC            => 'random',
                Fields::CUSTOMER_REF          => '1234566',
            ]
        ];

        $this->content($response, $this->action);

        $responseXml = $this->arrayToXml($response);

        return $this->makeXmlResponse($responseXml);
    }

    protected function xmlToArray($xml)
    {
        $res = null;

        try
        {
            $res = simplexml_load_string($xml);

            return json_decode(json_encode($res), true);
        }
        catch (\Exception $e)
        {
            assertTrue('XML decode failed');
        }
    }

    protected function arrayToXml(array $array, string $wrap = null)
    {
        // set initial value for XML string
        $xml = '';

        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                $xml .= $this->arrayToXml($value, $key);
            }
            else
            {
                $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
            }
        }

        // wrap XML with $wrap TAG
        if ($wrap !== null)
        {
            $xml = "<$wrap>".$xml."</$wrap>";
        }

        return $xml;
    }

    private function makeXmlResponse(string $xml)
    {
        $response = parent::makeResponse($xml);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        return $content;
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
        $gatewayPaymentId = empty($upiEntity[Entity::GATEWAY_PAYMENT_ID]) ? 'dummy' :
                                $upiEntity[Entity::GATEWAY_PAYMENT_ID];
        $data = [
            Fields::UPI_PUSH_REQUEST => [
                Fields::GATEWAY_TRANSACTION_ID      => $gatewayPaymentId,
                Fields::UPI_TRANSACTION_ID          => str_random(20),
                Fields::CUSTOMER_REF                => random_integer(12),
                Fields::AMOUNT                      => $upiEntity[Entity::AMOUNT] / 100,
                Fields::TRANSACTION_STATUS          => Status::SUCCESS,
                Fields::PAYER_VPA                   => $upiEntity[Entity::VPA] ?? 'random@icici',
                Fields::PAYER_VERIFIED_NAME         => 'Customer Name',
                Fields::PAYEE_VPA                   => 'aggrvpa2@rblbank',
                Fields::PAYEE_VERIFIED_NAME         => 'AGGREGATORKANAME',
                Fields::PAYEE_MOBILE                => '919830412697',
                Fields::MERCHANT_ID                 => 'MID',
                Fields::AGGR_ID                     => 'MID',
                Fields::TRANSACTION_DATE_TIME       => '2018-09-10 17:04:50:049',
                Fields::REFID                       => $payment['id'],
                Fields::REFURL                      => 'http://www.rbl.com',
            ]
        ];

        $this->content($data, 'callback');

        $data = $this->arrayToXml($data);

        $encryptedArray = ['data' => $this->encryptAes($data)];

        $content = $this->arrayToXml($encryptedArray);

        return $content;
    }


    public function encryptAes(string $stringToEncrypt)
    {
        return $this->getGatewayInstance()->getEncryptedString($stringToEncrypt);
    }

    public function decryptAes(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }
}
