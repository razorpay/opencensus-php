<?php

namespace RZP\Gateway\Esigner\Digio\Mock;

use Request;
use RZP\Gateway\Base;
use Lib\Formatters\Xml;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        // $this->validateAuthorizeInput($input);

        $inputArray = json_decode(json_decode($input, true)['content'], true);

        $accountNumber = $inputArray['customer_account_number'];

        if ($accountNumber === '914010009305864')
        {
            $response = [
                'details'    => str_random(20),
                'code'       => 'REQUEST_VALIDATION_FAILED',
                'message'    => 'Invalid Aadhaar id',
            ];

            return $this->makeJsonResponse($response, 502);
        }

        $response = [
            'id' => str_random(20),
            'enach_type' => 'CREATE',
        ];

        return $this->makeJsonResponse($response);
    }

    public function callback($input)
    {
        $this->content($input);

        $xmlContent = [

        ];

        $this->content($xmlContent, 'callback');

        $xml = Xml::create('Document', $xmlContent);

        $response = $this->makeResponse($xml);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    public function verify($input)
    {
        // In verify action we calls the gateway twice:
        // 1. For fetching the mandate status
        // 2. For fetching the signed xml

        // This is for fetching the signed xml
        if (isset($input['mandate_id']) === true)
        {
            $xml = Xml::create('Document', []);

            $response = $this->makeResponse($xml);

            $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

            return $response;
        }

        // For fetching the mandate status
        $request = $this->mockRequest;

        $data = $this->getVerifyResponse($request);

        return $this->makeResponse(json_encode($data));
    }

    public function sign($input)
    {
        $request = [
            'url' => $input['redirect_url'],
            'method' => 'POST',
            'content' => [
                'status' => 'success',
                'message' => 'Signing Success',
                'digio_doc_id' => str_random(40)
            ]
        ];

        $this->content($request, 'sign');

        return $this->makePostResponse($request);
    }

    protected function makeJsonResponse(array $content, $statusCode = 200)
    {
        $json = json_encode($content);

        $response = \Response::make($json, $statusCode);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getVerifyResponse($request)
    {
        $requestArray = explode('/', $request['url']);

        $mandateId = end($requestArray);

        $data = [
            'id'             => $mandateId,
            'enach_type'     => 'CREATE',
            'status'         => 'signed',
            'partner_entity' => [
                'email'        => 'enach@sponsorbank.com',
                'status'       => 'downloaded',
                'last_updated' => "2017-11-13 13:44:46"
            ]
        ];

        return $data;
    }
}
