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
        $data = json_decode($input['json'], true);

        $request = [
            'url' => $data['callback_url'],
            'method' => 'POST',
            'content' => [
                'digio_mandate_id' => str_random(20)
            ]
        ];

        return $this->makePostResponse($request);
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}