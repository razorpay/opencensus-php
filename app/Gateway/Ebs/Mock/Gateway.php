<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Ebs;
use RZP\Gateway\Ebs\BankCodes;
use Requests_Response;
use Requests_Cookie;
use Requests_Cookie_Jar;
use Requests_Response_Headers;

class Gateway extends Ebs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    public function sendFirstGatewayRequestForEbsAuthorize($request)
    {
        $this->content = $request['content'];

        $cookie = [
            'PGSID' => 'value1',
            'PIDs'  => 'value2',
            'sid'   => 'value3',
        ];

        $header = ['location'=> 'https://api.razorpay.com'];

        // For Central Bank of India Fail First Gateway Request
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::CBIN))
        {
            $header = [];
        }

        $response = $this->createResponse('302', false);

        $response = $this->setCookie($response, $cookie);
        $response = $this->setBody($response, '');
        $response = $this->setHeader($response, $header);

        return $response;
    }

    public function sendSecondGatewayRequestForEbsAuthorize($request)
    {
        $header = ['location'=> 'https://api.razorpay.com'];

        $response = $this->createResponse();

        $response = $this->setBody($response, $this->getText());
        $response = $this->setHeader($response, $header);

        $response = $this->setBody($response, $this->getText($this->content));

        // For Canara Bank Fail Second Gateway Request
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::CNRB))
        {
            $response = $this->setBody($response, '');
        }

        return $response;
    }

    public function sendThirdGatewayRequestForEbsAuthorize($request)
    {
        // For Union Bank of India, and Kotak and some banks
        // Redirection is done via 302 Page
        // For YES Bank and other banks
        // Redirection is done uisng Form post
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::UBIN))
        {
            $header = ['location'=> 'https://api.razorpay.com'];

            $response = $this->createResponse('302', false);

            $response = $this->setHeader($response, $header);
        }
        else
        {
            $response = $this->createResponse();
        }

        $response = $this->setBody($response, $this->getText($this->content));

        // For Corporation Bank Fail Third Gatteway Request,
        if ($this->content['payment_option'] === BankCodes::getMappedCode(IFSC::JAKA))
        {
            $response = $this->setBody($response, '');
        }

        return $response;
    }

    protected function setHeader($response, $headerValue)
    {
        $header = new Requests_Response_Headers();

        foreach ($headerValue as $key => $value)
        {
            $header->offsetSet($key, $value);
        }

        $response->headers = $header;

        return $response;
    }

    protected function setCookie($response, $cookieValue)
    {
        $cookie = [];

        foreach ($cookieValue as $key => $value)
        {
            $cookie[] = new Requests_Cookie($key, $value);
        }

        $cookies = new Requests_Cookie_Jar($cookie);

        $response->cookies = $cookies;

        return $response;
    }

    protected function setBody($response, $body)
    {
        $response->body = $body;

        return $response;
    }

    protected function createResponse($statusCode = 200, $success = true)
    {
        $response = new Requests_Response();

        $response->status_code = $statusCode;
        $response->success = $success;

        return $response;
    }

    protected function getText($content = [])
    {
        $txt = '<form method="POST" name="payment" action = "https://api.razorpay.com">';

        foreach ($content as $key => $value)
        {
            $txt .= '<input type="hidden" name="' . $key. '" value="' . $value . '">';
        }

        $txt .= '</form>';

        return $txt;
    }

    /*
     * Mocking this Function as mocked server need the requets to be POST,
    */
    public function getRequest($location, $method, $body)
    {
        $request = [
            'url' => $location,
            'method' => 'post',
            'content' => $this->content,
        ];

        return $request;
    }
}
