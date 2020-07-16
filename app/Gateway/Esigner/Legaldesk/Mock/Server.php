<?php

namespace RZP\Gateway\Esigner\Legaldesk\Mock;

use RZP\Gateway\Base;
use Lib\Formatters\Xml;
use RZP\Models\Payment;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
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

    public function verify($input)
    {
        $input = json_decode($input, true);

        $response = $this->getXmlFetchResponse($input);

        return $this->makeJsonResponse($response);
    }

    /**
     * @param $input
     * @return mixed
     *
     * We do not send the callback URL in the signing request
     * Instead, we send it in the mandate create request and store it
     * in their backend.
     *
     * Hence, we fetch the payment id from the mandate id stored in esigner
     * entity and then generate the corresponding callback URL and redirect to that
     * from the mock signing request.
     */
    public function sign($input)
    {
        $content = [
            'mandate_status' => 'success',
            'message'        => 'Signing success',
            'emandate_id'    => $input['mandate_id'],
            'reference_id'   => $input['reference_id'],
        ];

        $this->content($content, 'mandate_sign');

        $callbackUrl = $this->route->getUrlWithPublicAuth('gateway_payment_static_callback_post',
                                    [
                                        'method'    => 'emandate',
                                        'gateway'   => 'esigner_legaldesk',
                                        'mode'      => 'test',
                                    ]);

        $request = [
            'url'     => $callbackUrl,
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
            ResponseFields::STATUS              => 'success',
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::ERROR               => 'NA',
            ResponseFields::ERROR_CODE          => 'NA',
            ResponseFields::API_RESPONSE_ID     => '5b6c51139788dd40bd25ded6',
            ResponseFields::CONTENT             => base64_encode($xml),
            ResponseFields::CONTENT_TYPE        => 'xml',
        ];

        $this->content($content, 'fetch_mandate_content');

        return $content;
    }

    protected function getMandateCreateResponse($input)
    {
        $mandateId = $this->generateRandomNumber(15);

        $response = [
            ResponseFields::STATUS              => 'success',
            ResponseFields::API_RESPONSE_ID     => '5b6c51139788dd40bd25ded6',
            ResponseFields::REFERENCE_ID        => '987654321',
            ResponseFields::ERROR               => 'NA',
            ResponseFields::ERROR_CODE          => 'NA',
            ResponseFields::EMANDATE_ID         => $mandateId,
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::QUICK_INVITE_URL    => $this->getMockPaymentGatewayUrl($mandateId, $input['reference_id'])
        ];

        $this->content($response, 'mandate_create');

        return $response;
    }

    protected function getXmlFetchResponse($input)
    {
        $xmlContent = [];

        $this->content($xmlContent, 'fetch_mandate_xml');

        $xml = Xml::create('Document', $xmlContent);

        return [
            ResponseFields::STATUS              => 'success',
            ResponseFields::API_RESPONSE_ID     => '5b6c51139788dd40bd25ded6',
            ResponseFields::ERROR               => 'NA',
            ResponseFields::ERROR_CODE          => 'NA',
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::CONTENT             => base64_encode($xml),
            ResponseFields::CONTENT_TYPE        => 'xml',
        ];
    }

    protected function getMockPaymentGatewayUrl($mandateId, $paymentId)
    {
        $route = 'mock_esigner_payment';

        return $this->route->getUrl($route, ['signer' => 'esigner_legaldesk']) . '?mandate_id=' . $mandateId.
            '&reference_id=' . $paymentId;
    }

    protected function generateRandomNumber($length)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++)
        {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
