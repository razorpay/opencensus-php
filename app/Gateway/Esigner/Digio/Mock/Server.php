<?php

namespace RZP\Gateway\Esigner\Digio\Mock;

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
        $xmlContent = [

        ];

        $this->content($xmlContent, 'callback');

        $xml = Xml::create('Document', $xmlContent);

        $response = $this->makeResponse($xml);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
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

        return $this->makePostResponse($request);
    }

    protected function makeJsonResponse(array $content, $statusCode = 200)
    {
        $json = json_encode($content);

        $response = \Response::make($json, $statusCode);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
