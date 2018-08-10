<?php

namespace RZP\Gateway\Esigner\Legaldesk\Mock;

use RZP\Gateway\Base;
use Lib\Formatters\Xml;
use RZP\Gateway\Esigner\Legaldesk\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = json_decode($input, true);

        $response = $this->getMandateCreateResponse($input);

        return $this->makeJsonResponse($response);
    }

    public function callback($input)
    {
        $responseContent = $this->getFetchMandateXmlResponseContent();

        return $this->makeJsonResponse($responseContent);
    }

    public function sign($input)
    {
        $content = [
            'status'      => 'success',
            'message'     => 'Signing success',
            'emandate_id' => $input['mandate_id'],
        ];

        $url = $this->route->getUrl('gateway_payment_callback_legaldesk');

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => $content,
        ];

        return $this->makePostResponse($request);
    }

    protected function getFetchMandateXmlResponseContent()
    {
        $xmlContent = [];

        $this->content($xmlContent, 'fetch_mandate_xml');

        $xml = Xml::create('Document', $xmlContent);

        $content = [
            ResponseFields::STATUS => 'success',
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::ERROR => 'NA',
            ResponseFields::ERROR_CODE => 'NA',
            ResponseFields::API_RESPONSE_ID => '5b6c51139788dd40bd25ded6',
            ResponseFields::CONTENT => base64_encode($xml),
            ResponseFields::CONTENT_TYPE => 'xml',
        ];

        $this->content($content, 'fetch_mandate_content');

        return $content;
    }

    protected function getMandateCreateResponse($input)
    {
        $mandateId = $this->generateRandomNumber(15);

        return [
            ResponseFields::STATUS              => 'success',
            ResponseFields::API_RESPONSE_ID     => '5b6c51139788dd40bd25ded6',
            ResponseFields::REFERENCE_ID        => '987654321',
            ResponseFields::ERROR               => 'NA',
            ResponseFields::ERROR_CODE          => 'NA',
            ResponseFields::EMANDATE_ID         => $mandateId,
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::QUICK_INVITE_URL    => $this->getMockPaymentGatewayUrl($mandateId)
        ];
    }

    protected function getMockPaymentGatewayUrl($mandateId)
    {
        $route = 'mock_esigner_payment';

        return $this->route->getUrl($route, ['signer' => 'legaldesk']) . '?mandate_id=' . $mandateId;
    }

    protected function generateRandomNumber($length)
    {
        $result = '';

        for($i = 0; $i < $length; $i++)
        {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }
}
